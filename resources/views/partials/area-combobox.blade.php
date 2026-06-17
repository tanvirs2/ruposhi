{{-- Searchable area combobox — drop-in replacement for a plain <select> of
     areas (handles hundreds without a giant dropdown). Keeps the selected id
     in a hidden input, so existing getElementById(...) lookups and form
     name="area_id" submits keep working unchanged. A native `change` event is
     dispatched on the hidden input when the selection changes, so old
     onchange handlers can listen on it. Wired by initAreaComboboxes() in app.js.

     Params (via @include):
       $areas          required — collection with ->id / ->name
       acId            hidden input id (optional)
       acName          hidden input name        (default 'area_id')
       acValue         pre-selected area id      (default '')
       acClass         css class for the search input (default 'form-select')
       acPlaceholder   placeholder text          (default '— সব এলাকা —')
       acAllLabel      label for the "clear" row (default '— সব এলাকা —')
--}}
@php
    $acId          = $acId          ?? null;
    $acName        = $acName        ?? 'area_id';
    $acValue       = $acValue       ?? '';
    $acClass       = $acClass       ?? 'form-select';
    $acPlaceholder = $acPlaceholder ?? '— সব এলাকা —';
    $acAllLabel    = $acAllLabel    ?? '— সব এলাকা —';
    $acSelectedName = '';
    if ($acValue !== '' && $acValue !== null) {
        $acSelectedName = optional($areas->firstWhere('id', (int) $acValue))->name ?? '';
    }
@endphp
<div class="area-combobox" style="position:relative" data-all-label="{{ $acAllLabel }}">
    <input type="text" class="{{ $acClass }} ac-search" autocomplete="off"
           placeholder="{{ $acPlaceholder }}" value="{{ $acSelectedName }}" style="width:100%">
    <input type="hidden" @if($acId) id="{{ $acId }}" @endif name="{{ $acName }}" value="{{ $acValue }}" class="ac-id">
    <script type="application/json" class="ac-data">@json($areas->map(fn($a) => ['id' => $a->id, 'name' => $a->name])->values(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)</script>
</div>
