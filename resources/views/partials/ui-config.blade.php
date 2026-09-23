@php
    /** @var \LimenAi\Ui\ChatUiConfig $uiConfig */
    $uiConfig = app(\LimenAi\Ui\ChatUiConfig::class);
@endphp
data-ui-config="{{ e($uiConfig->toJson()) }}"
