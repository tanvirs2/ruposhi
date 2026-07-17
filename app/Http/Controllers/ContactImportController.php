<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerArea;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-import customers/suppliers from CSV — for onboarding a new shop whose
 * dues live in a paper ledger. The due goes into opening_balance, not
 * due_amount: the ledger auto-fix recomputes due_amount from transactions on
 * every view, so a directly-written figure would be wiped (opening_balance
 * feeds that formula instead).
 *
 * Upload is a two-step flow — preview first, then confirm — because a bulk
 * insert of hundreds of rows is not something to trigger on a stray click.
 */
class ContactImportController extends Controller
{
    /** Column order of the template, and what each maps to. */
    private const COLS_CUSTOMER = ['নাম', 'প্রোপ্রাইটর', 'ফোন', 'ঠিকানা', 'এলাকা', 'পুরনো বাকী', 'ক্রেডিট লিমিট'];
    private const COLS_SUPPLIER = ['নাম', 'প্রোপ্রাইটর', 'ফোন', 'ইমেইল', 'ঠিকানা', 'পুরনো দেনা'];

    private function isSupplier(string $type): bool
    {
        return $type === 'suppliers';
    }

    private function guard(string $type): void
    {
        abort_unless(in_array($type, ['customers', 'suppliers'], true), 404);
        abort_unless(auth()->user()->canManageShop(), 403);
    }

    public function form(string $type)
    {
        $this->guard($type);

        return view('contacts.import', [
            'type'    => $type,
            'columns' => $this->isSupplier($type) ? self::COLS_SUPPLIER : self::COLS_CUSTOMER,
            'label'   => $this->isSupplier($type) ? 'সরবরাহকারী' : 'কাস্টমার',
            'dueLbl'  => $this->isSupplier($type) ? 'পুরনো দেনা' : 'পুরনো বাকী',
        ]);
    }

    /** Blank CSV with the right headers + one example row. */
    public function template(string $type)
    {
        $this->guard($type);

        $cols = $this->isSupplier($type) ? self::COLS_SUPPLIER : self::COLS_CUSTOMER;
        $demo = $this->isSupplier($type)
            ? ['মেসার্স দাদা রাইস মিলস', 'মোঃ সেলিম', '01711000000', 'dada@example.com', 'কুষ্টিয়া', '50000']
            : ['মেসার্স করিম স্টোর', 'মোঃ করিম', '01712000000', 'বড় বাজার', 'মিরপুর', '50000', '100000'];

        $csv = "\xEF\xBB\xBF" . $this->csvLine($cols) . $this->csvLine($demo);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $type . '-template.csv"',
        ]);
    }

    /** Step 1 — parse + validate, show what would happen. Writes nothing. */
    public function preview(Request $request, string $type)
    {
        $this->guard($type);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);

        $parsed = $this->parse($request->file('file')->getRealPath(), $type);

        if (empty($parsed['rows'])) {
            return back()->with('error', 'CSV-তে কোনো সারি পাওয়া যায়নি। টেমপ্লেট ডাউনলোড করে দেখুন।');
        }

        // Stash for the confirm step — small payload, session is fine
        session(['contact_import' => ['type' => $type, 'rows' => $parsed['rows']]]);

        return view('contacts.import', [
            'type'    => $type,
            'columns' => $this->isSupplier($type) ? self::COLS_SUPPLIER : self::COLS_CUSTOMER,
            'label'   => $this->isSupplier($type) ? 'সরবরাহকারী' : 'কাস্টমার',
            'dueLbl'  => $this->isSupplier($type) ? 'পুরনো দেনা' : 'পুরনো বাকী',
            'preview' => $parsed,
        ]);
    }

    /** Step 2 — actually insert the rows marked 'new'. */
    public function commit(Request $request, string $type)
    {
        $this->guard($type);

        $stash = session('contact_import');
        if (!$stash || $stash['type'] !== $type) {
            return redirect()->route('contacts.import.form', $type)
                ->with('error', 'প্রিভিউ মেয়াদ শেষ। আবার আপলোড করুন।');
        }

        $toAdd = array_filter($stash['rows'], fn($r) => $r['status'] === 'new');
        if (empty($toAdd)) {
            return redirect()->route('contacts.import.form', $type)
                ->with('error', 'যোগ করার মতো নতুন সারি নেই।');
        }

        $areaCache = [];
        $added     = 0;

        DB::transaction(function () use ($toAdd, $type, &$areaCache, &$added) {
            foreach ($toAdd as $r) {
                if ($this->isSupplier($type)) {
                    Supplier::create([
                        'name'            => $r['name'],
                        'proprietor'      => $r['proprietor'] ?: null,
                        'phone'           => $r['phone'] ?: null,
                        'email'           => $r['email'] ?: null,
                        'address'         => $r['address'] ?: null,
                        'opening_balance' => $r['opening_balance'],
                    // A fresh contact has no transactions, so due = opening_balance.
                    // Set it now so the list shows the imported due right away
                    // instead of "পরিষ্কার" until each ledger is opened one by one.
                    ])->recalcDue();
                } else {
                    $areaId = null;
                    if ($r['area'] !== '') {
                        // Auto-create areas that don't exist yet (shop-scoped by the trait)
                        $key = mb_strtolower($r['area']);
                        $areaId = $areaCache[$key] ??= CustomerArea::firstOrCreate(['name' => $r['area']])->id;
                    }
                    Customer::create([
                        'name'            => $r['name'],
                        'proprietor'      => $r['proprietor'] ?: null,
                        'phone'           => $r['phone'] ?: null,
                        'address'         => $r['address'] ?: null,
                        'area_id'         => $areaId,
                        'opening_balance' => $r['opening_balance'],
                        'credit_limit'    => $r['credit_limit'],
                    // See supplier note above — sync due_amount on import.
                    ])->recalcDue();
                }
                $added++;
            }
        });

        session()->forget('contact_import');
        $label = $this->isSupplier($type) ? 'সরবরাহকারী' : 'কাস্টমার';

        return redirect()->route($type . '.index')
            ->with('success', "{$added}টি {$label} পুরনো বাকীসহ যোগ করা হয়েছে।");
    }

    // ── helpers ──────────────────────────────────────────────────

    /**
     * Read the CSV and tag every row: new | duplicate | error.
     * Duplicates are reported, never updated — an import should not silently
     * rewrite a due someone already recorded.
     */
    private function parse(string $path, string $type): array
    {
        $isSup = $this->isSupplier($type);
        $rows  = [];
        $seen  = [];   // catches duplicates *within* the file too

        $existing = $isSup
            ? Supplier::get(['name', 'phone'])
            : Customer::get(['name', 'phone']);

        $byPhone = $existing->filter(fn($c) => $c->phone)->keyBy(fn($c) => $this->normPhone($c->phone));
        $byName  = $existing->keyBy(fn($c) => mb_strtolower(trim($c->name)));

        $fh = fopen($path, 'r');

        // Excel writes UTF-8 CSV with a BOM. It sits *before* the first field's
        // opening quote, so fgetcsv stops recognising that field as quoted and
        // hands back a literal "নাম" — which slipped past the header check and
        // got imported as a customer. Consume the BOM before parsing.
        if (fread($fh, 3) !== "\xEF\xBB\xBF") {
            rewind($fh);
        }

        $n = 0;
        while (($line = fgetcsv($fh)) !== false) {
            $n++;
            $line = array_map(fn($v) => trim((string) $v), $line);

            if ($n === 1 && in_array($line[0] ?? '', ['নাম', 'Name', 'name'], true)) continue;  // header
            if (count(array_filter($line, fn($v) => $v !== '')) === 0) continue;                 // blank

            $name = $line[0] ?? '';
            $row  = [
                'line'            => $n,
                'name'            => $name,
                'proprietor'      => $line[1] ?? '',
                'phone'           => $line[2] ?? '',
                'email'           => $isSup ? ($line[3] ?? '') : '',
                'address'         => $isSup ? ($line[4] ?? '') : ($line[3] ?? ''),
                'area'            => $isSup ? '' : ($line[4] ?? ''),
                'opening_balance' => $this->num($isSup ? ($line[5] ?? '') : ($line[5] ?? '')),
                'credit_limit'    => $isSup ? null : ($this->num($line[6] ?? '') ?: null),
                'status'          => 'new',
                'note'            => '',
            ];

            $phoneKey = $this->normPhone($row['phone']);
            $nameKey  = mb_strtolower($name);

            if ($name === '') {
                $row['status'] = 'error';
                $row['note']   = 'নাম খালি';
            } elseif (isset($seen[$nameKey]) || ($phoneKey && isset($seen['p:' . $phoneKey]))) {
                $row['status'] = 'duplicate';
                $row['note']   = 'এই ফাইলেই আগে আছে';
            } elseif ($phoneKey && $byPhone->has($phoneKey)) {
                $row['status'] = 'duplicate';
                $row['note']   = 'একই ফোনে আগে থেকেই আছে';
            } elseif ($byName->has($nameKey)) {
                $row['status'] = 'duplicate';
                $row['note']   = 'একই নামে আগে থেকেই আছে';
            }

            if ($row['status'] === 'new') {
                $seen[$nameKey] = true;
                if ($phoneKey) $seen['p:' . $phoneKey] = true;
            }

            $rows[] = $row;
        }
        fclose($fh);

        return [
            'rows'  => $rows,
            'new'   => count(array_filter($rows, fn($r) => $r['status'] === 'new')),
            'dupe'  => count(array_filter($rows, fn($r) => $r['status'] === 'duplicate')),
            'error' => count(array_filter($rows, fn($r) => $r['status'] === 'error')),
            'sumDue' => array_sum(array_map(fn($r) => $r['status'] === 'new' ? $r['opening_balance'] : 0, $rows)),
        ];
    }

    /** Bengali digits, ৳, commas and spaces all appear in hand-made sheets. */
    private function num(string $v): float
    {
        $v = strtr(trim($v), ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']);
        $v = preg_replace('/[^0-9.\-]/', '', $v);
        return $v === '' || $v === '-' ? 0.0 : (float) $v;
    }

    private function normPhone(?string $p): string
    {
        $p = preg_replace('/\D/', '', strtr((string) $p, ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']));
        return $p === '' ? '' : substr($p, -10);   // ignore +880 / 0 prefixes
    }

    private function csvLine(array $cells): string
    {
        return implode(',', array_map(fn($c) => '"' . str_replace('"', '""', (string) $c) . '"', $cells)) . "\r\n";
    }
}
