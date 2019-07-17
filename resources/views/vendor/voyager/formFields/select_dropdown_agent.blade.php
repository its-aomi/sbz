@php
    $optionsArray = clone $options;
    $optionsArray = (array)$optionsArray;
    $roleOptions = (isset($optionsArray['roleOptions']) ? (array)$optionsArray['roleOptions'] : '');
    $currentUserRoleIsNotListed = false;
    if($roleOptions != '') {
        $roles = (array)$roleOptions['readonly'];
        $noRoles = false;
    } else {
        $noRoles = true;
    }
@endphp

<?php $selected_value = (isset($dataTypeContent->{$row->field}) && !is_null(old($row->field, $dataTypeContent->{$row->field}))) ? old($row->field, $dataTypeContent->{$row->field}) : old($row->field); ?>
@php
    $model = app('App\User');
    $users = $model::all();
@endphp  
@if (!$noRoles) 
    @foreach ($roles as $readonlyUserRole)
        @if ($currentUserRole == strtolower($readonlyUserRole))
            {{-- {{ dd($users) }} --}}
            <select name="{{ $row->field }}" class="form-control" disabled="disabled">
                <optgroup label="{{ __('voyager::generic.custom') }}">
                @foreach($users as $key => $user)
                    @if ((string)$selected_value == (string)$user->id)
                        <option value="{{ ($user->id == '_empty_' ? '' : $user->id) }}"  @if((int)$selected_value == (int)$user->id){{ 'selected="selected"' }}@endif>{{ $user->user_name }}</option>
                    @endif
                @endforeach
                </optgroup> 
            </select>
            @php
                $currentUserRoleIsNotListed = false;
            @endphp
            
        @else
            @php
                $currentUserRoleIsNotListed = true;
            @endphp
        @endif   

    @endforeach

    @if ($currentUserRoleIsNotListed)
        <select class="form-control select2" name="{{ $row->field }}">
            <?php $default = (isset($options->default) && !isset($dataTypeContent->{$row->field})) ? $options->default : null; ?>
            <option disabled selected value> -- Select an agent -- </option>
            @foreach($users as $key => $user)
                <option value="{{ $user->id }}"@if((string)$selected_value == (string)$user->id){{ 'selected="selected"' }}@endif>{{ $user->user_name }}</option>
            @endforeach
            
        </select>
    @endif
@else 
    <select class="form-control select2" name="{{ $row->field }}">
        <?php $default = (isset($options->default) && !isset($dataTypeContent->{$row->field})) ? $options->default : null; ?>
        <option disabled selected value> -- Select an agent -- </option>
        @foreach($users as $key => $user)
            <option value="{{ $user->id }}"@if( (string)$selected_value == (string)$user->id){{ 'selected="selected"' }}@endif>{{ $user->user_name }}</option>
        @endforeach
    </select>
@endif
