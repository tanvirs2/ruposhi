@php
    $drFrom = $fromName ?? 'from';
    $drTo   = $toName   ?? 'to';
    $drForm = $formClass ?? '.filter-form';
@endphp
<div style="display:flex;align-items:center;gap:4px;align-self:flex-end">
    <button type="button" onclick="drRange('{{ $drFrom }}','{{ $drTo }}','{{ $drForm }}','this_month')" class="btn btn-ghost" style="font-size:.8rem;padding:6px 11px">এই মাস</button>
    <button type="button" onclick="drRange('{{ $drFrom }}','{{ $drTo }}','{{ $drForm }}','last_month')" class="btn btn-ghost" style="font-size:.8rem;padding:6px 11px">গত মাস</button>
    <button type="button" onclick="drRange('{{ $drFrom }}','{{ $drTo }}','{{ $drForm }}','this_year')"  class="btn btn-ghost" style="font-size:.8rem;padding:6px 11px">এই বছর</button>
    <button type="button" onclick="drRange('{{ $drFrom }}','{{ $drTo }}','{{ $drForm }}','all')"        class="btn btn-ghost" style="font-size:.8rem;padding:6px 11px">সব</button>
</div>
