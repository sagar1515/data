
<?php
    $auth_user= authSession();
?>
{{ html()->form('DELETE', route('providerdocument.destroy', $provider_document->id))->attribute('data--submit', 'providerdocument'.$provider_document->id)->open() }}
<div class="d-flex justify-content-end align-items-center ms-2">
    @if(!$provider_document->trashed())
 

        @if($auth_user->can('providerdocument delete'))
        <a class="me-3" href="{{ route('providerdocument.destroy', $provider_document->id) }}" data--submit="providerdocument{{$provider_document->id}}" 
            data--confirmation='true' 
            data--ajax="true"
            data-datatable="reload"
            data-title="{{ __('messages.delete_form_title',['form'=>  __('messages.providerdocument') ]) }}"
            title="{{ __('messages.delete_form_title',['form'=>  __('messages.providerdocument') ]) }}"
            data-message='{{ __("messages.delete_msg") }}'>
            <i class="far fa-trash-alt text-danger"></i>
        </a>
        @endif
    @endif
    @if(auth()->user()->hasAnyRole(['admin']) && $provider_document->trashed())
        <a href="{{ route('providerdocument.action',['id' => $provider_document->id, 'type' => 'restore']) }}"
            title="{{ __('messages.restore_form_title',['form' => __('messages.providerdocument') ]) }}"
            data--submit="confirm_form"
            data--confirmation='true'
            data--ajax='true'
            data-title="{{ __('messages.restore_form_title',['form'=>  __('messages.providerdocument') ]) }}"
            data-message='{{ __("messages.restore_msg") }}'
            data-datatable="reload"
            class="me-2">
            <i class="fas fa-redo text-secondary"></i>
        </a>
        <a href="{{ route('providerdocument.action',['id' => $provider_document->id, 'type' => 'forcedelete']) }}"
            title="{{ __('messages.forcedelete_form_title',['form' => __('messages.providerdocument') ]) }}"
            data--submit="confirm_form"
            data--confirmation='true'
            data--ajax='true'
            data-title="{{ __('messages.forcedelete_form_title',['form'=>  __('messages.providerdocument') ]) }}"
            data-message='{{ __("messages.forcedelete_msg") }}'
            data-datatable="reload"
            class="me-2">
            <i class="far fa-trash-alt text-danger"></i>
        </a>
    @endif
    {{ html()->form()->close() }}
