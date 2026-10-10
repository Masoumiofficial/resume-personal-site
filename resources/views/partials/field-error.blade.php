@error($name, $bag ?? 'default')
  <span class="field__error" id="{{ $name }}-error">{{ $message }}</span>
@enderror
