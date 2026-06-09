@php
    $drFrom = $fromName ?? 'from';
    $drTo   = $toName   ?? 'to';
    $drForm = $formClass ?? '.filter-form';
@endphp
<button type="button" onclick="drRange('{{ $drFrom }}','{{ $drTo }}','{{ $drForm }}','this_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই মাস</button>
<button type="button" onclick="drRange('{{ $drFrom }}','{{ $drTo }}','{{ $drForm }}','last_month')" class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">গত মাস</button>
<button type="button" onclick="drRange('{{ $drFrom }}','{{ $drTo }}','{{ $drForm }}','this_year')"  class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">এই বছর</button>
<button type="button" onclick="drRange('{{ $drFrom }}','{{ $drTo }}','{{ $drForm }}','all')"        class="btn btn-ghost" style="font-size:.8rem;padding:8px 12px">সব</button>
