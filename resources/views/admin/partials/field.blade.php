@php
  $name = $field['name'];
  $type = $field['type'];
  $id = 'f-'.$name;
  $submitted = old('_token') !== null;
  $current = $type === 'checkbox'
      ? ($submitted ? (bool) old($name) : (bool) $value)
      : ($type === 'checklist' ? array_map('strval', (array) old($name, $value ?? [])) : old($name, $value));
  $error = $errors->first($name) ?: $errors->first($name.'.*') ?: ($type === 'links' ? ($errors->first($name.'.*.url') ?: $errors->first($name.'.*.label')) : null);
  $ltr = ($field['dir'] ?? null) === 'ltr' || in_array($type, ['url', 'email'], true);
  $required = $field['required'] ?? false;
  $hints = [
      'jdate' => 'شمسی: ۱۴۰۲ یا ۱۴۰۲/۰۱ یا ۱۴۰۲/۰۱/۱۵',
      'jdatetime' => 'شمسی، به وقت تهران: ۱۴۰۵/۰۷/۱۸ ۱۴:۳۰',
      'money' => 'به تومان؛ جداکننده و رقم فارسی هم پذیرفته می‌شود.',
      'lines' => 'هر مورد در یک خط.',
      'links' => 'هر لینک در یک خط: عنوان | نشانی (مثل: لینکدین | https://…)',
      'markdown' => 'Markdown: ## تیتر، **پررنگ**، [متن](نشانی)، - فهرست. HTML خام نمایش داده نمی‌شود.',
  ];
  $hint = $field['hint'] ?? ($hints[$type] ?? null);
  $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
@endphp

<div @class(['field', 'form-grid__wide' => ($field['wide'] ?? false) || in_array($type, ['textarea', 'markdown', 'lines', 'links', 'checklist'], true)])>
  @if ($type === 'checkbox')
    <label class="checkbox" for="{{ $id }}">
      <input type="hidden" name="{{ $name }}" value="0">
      <input id="{{ $id }}" name="{{ $name }}" type="checkbox" value="1" @checked($current) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
      <span>{{ $field['label'] }}</span>
    </label>
  @elseif ($type === 'checklist')
    <fieldset class="checklist" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
      <legend class="field__label">{{ $field['label'] }}</legend>
      @forelse ($field['options'] ?? [] as $optionValue => $optionLabel)
        <label class="checkbox"><input name="{{ $name }}[]" type="checkbox" value="{{ $optionValue }}" @checked(in_array((string) $optionValue, $current, true))><span>{{ $optionLabel }}</span></label>
      @empty
        <span class="text-sm text-muted">{{ $field['empty'] ?? 'گزینه‌ای وجود ندارد.' }}</span>
      @endforelse
    </fieldset>
  @else
    <label class="field__label" for="{{ $id }}">{{ $field['label'] }}@if ($required)<span class="required-mark" aria-hidden="true">*</span>@endif</label>

    @if (in_array($type, ['textarea', 'markdown', 'lines', 'links'], true))
      <textarea @class(['textarea', 'textarea--code' => $type === 'markdown', 'input--ltr' => $ltr || $type === 'links'])
                id="{{ $id }}" name="{{ $name }}" rows="{{ $field['rows'] ?? ($type === 'markdown' ? 16 : 4) }}"
                @if ($required) required @endif @if ($error) aria-invalid="true" @endif
                @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $current }}</textarea>
    @elseif ($type === 'select' || $type === 'media')
      <select class="input" id="{{ $id }}" name="{{ $name }}" @if ($required) required @endif @if ($error) aria-invalid="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        @unless ($required && $type === 'select')<option value="">— {{ $type === 'media' ? 'بدون فایل' : 'انتخاب نشده' }} —</option>@endunless
        @foreach ($type === 'media' ? $media : ($field['options'] ?? []) as $optionValue => $optionLabel)
          <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
      </select>
    @else
      @php
        $inputType = match ($type) { 'email' => 'email', 'url' => 'url', default => 'text' };
        $inputMode = match ($type) { 'number', 'money' => 'numeric', default => null };
        $display = $type === 'money' && is_numeric($current) ? number_format((int) $current) : $current;
      @endphp
      <input @class(['input', 'input--ltr' => $ltr || in_array($type, ['jdate', 'jdatetime', 'number', 'money'], true)])
             id="{{ $id }}" name="{{ $name }}" type="{{ $inputType }}" value="{{ $display }}"
             @if ($inputMode) inputmode="{{ $inputMode }}" @endif
             @if (! empty($field['placeholder'])) placeholder="{{ $field['placeholder'] }}" @endif
             @if (! empty($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
             @if ($required) required @endif @if ($error) aria-invalid="true" @endif
             @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
             autocomplete="off">
    @endif
  @endif

  @if ($hint)<span class="field__hint" id="{{ $id }}-hint">{{ $hint }}</span>@endif
  @if ($error)<span class="field__error" id="{{ $id }}-error">{{ $error }}</span>@endif
</div>
