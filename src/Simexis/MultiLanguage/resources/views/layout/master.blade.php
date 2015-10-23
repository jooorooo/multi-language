<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="csrf-token" content="{{ csrf_token() }}" />

    <title>@lang('multilanguage::multilanguage.title')</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
    <script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet"/>
    <script src="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
    
    <link href="{{ action('\Simexis\MultiLanguage\Controllers\AssetController@getCss') }}" rel="stylesheet">

</head>
<body>
	
	<!-- /subnavbar -->
	<div class="container">
		@yield('content')
	</div>

    <script src="{{ action('\Simexis\MultiLanguage\Controllers\AssetController@getJs') }}"></script>
	
</body>
</html>
