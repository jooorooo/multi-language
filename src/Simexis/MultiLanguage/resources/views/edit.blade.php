@extends('multilanguage::layout.master')

@section('content')

	<div class="page-header">
		<div class="row">
			<div class="col-lg-11">
				<h1>{{ trans($locale ? 'multilanguage::multilanguage.edit_language' : 'multilanguage::multilanguage.create_language', ['name' => $locale?$locale->name:'']) }}</h1>
			</div>
		</div>
	</div>
    <div class="row">
        <div class="col-lg-12">
			@if(Session::has('message'))
				<div class="alert alert-{{ Session::get('type') }}">
					{{ Session::get('message') }}
				</div>
			@endif
			@if (count($errors) > 0)
				<div class="alert alert-danger">
					<ul>
						@foreach ($errors->all() as $error)
							<li>{{ $error }}</li>
						@endforeach
					</ul>
				</div>
			@endif
			
			<form action="{{ $locale ? action('\Simexis\MultiLanguage\Controllers\MultilanguageController@postLanguageEdit', [$locale->{config('multilanguage.locale_key')}]) : action('\Simexis\MultiLanguage\Controllers\MultilanguageController@postLanguageCreate') }}" method="post">
				<input type="hidden" name="_token" value="{{ csrf_token() }}">
				
				<div class="box">
					<div class="box-body">
						<div class="form-group col-md-12">
							<div class="col-md-2">@lang('multilanguage::multilanguage.entries.name'):</div>
							<div class="col-md-10">
								<input type="text" class="form-control" name="name" value="{{ old('name', $locale ? $locale->name : '') }}">
							</div>
						</div>
						<div class="form-group col-md-12">
							<div class="col-md-2">@lang('multilanguage::multilanguage.entries.locale'):</div>
							<div class="col-md-10">
								<input type="text" {{ $locale && $locale->{config('multilanguage.locale_key')} == $protected ? 'readonly="readonly"' : '' }} class="form-control" name="{{ config('multilanguage.locale_key') }}" value="{{ old(config('multilanguage.locale_key'), $locale ? $locale->{config('multilanguage.locale_key')} : '') }}">
								<span class="help-block"><a href="https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes" target="_blank">@lang('multilanguage::multilanguage.language_iso_list')</a></span>
							</div>
						</div>
					</div>

                    <div class="box-footer text-center">
						<button type="submit" name="save" class="btn btn-primary">@lang('multilanguage::multilanguage.actions.save')</button>
                    </div>
				</div>
				
			</form>
			
        </div>
    </div>

@stop