@extends('multilanguage::layout.master')

@section('content')

	<div class="page-header">
		<div class="row">
			<div class="col-lg-5">
				<h1>@lang('multilanguage::multilanguage.title')</h1>
			</div>
			<div class="col-lg-6">
				<form class="form-inline form-global-action" method="POST" action="{{ action('\Simexis\MultiLanguage\Controllers\MultilanguageController@postImport') }}">
					<input type="hidden" name="_token" value="{{ csrf_token() }}">
					<select name="replace" class="form-control">
						@foreach(Lang::get('multilanguage::multilanguage.form_actions') AS $action => $title)
						<option value="{{ $action }}">{{ $title }}</option>
						@endforeach
					</select>
					<button type="submit" class="btn btn-success"  data-disable-with="@lang('multilanguage::multilanguage.actions.loading')">@lang('multilanguage::multilanguage.actions.submit')</button>
				</form>
			</div>
			<div class="col-lg-1">
				<a href="{{ action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getLanguageCreate') }}" title="@lang('multilanguage::multilanguage.actions.new_title')" data-toggle="tooltip" data-placement="left" class="btn btn-default">@lang('multilanguage::multilanguage.actions.new')</a>
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
			
            <table class="table">
				<thead>
					<tr>
						<th class="name">@lang('multilanguage::multilanguage.entries.name')</th>
						<th class="locale">@lang('multilanguage::multilanguage.entries.locale')</th>
						<th class="lprogress">@lang('multilanguage::multilanguage.entries.complete')</th>
						<th class="actions">@lang('multilanguage::multilanguage.actions.actions')</th>
					</tr>
				</thead>
				<tbody>
					@foreach($locales AS $locale)
					<tr>
						<td>{{ $locale->name }}</td>
						<td class="text-center">{{ $locale->{config('multilanguage.locale_key')} }}</td>
						<td>
							<div class="progress">
								<div class="progress-bar" role="progressbar" aria-valuenow="{{ $locale->progress}}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $locale->progress < 10 ? 10 : $locale->progress}}%;">
								{{ $locale->progress}}%
								</div>
							</div>
						</td>
						<td class="text-right">
							<a href="{{ action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getLanguageEdit', [$locale->{config('multilanguage.locale_key')}]) }}" title="@lang('multilanguage::multilanguage.actions.edit')" data-toggle="tooltip" data-placement="top"><i class="glyphicon glyphicon-pencil"></i></a>
							<a href="{{ action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getTranslations', [$locale->{config('multilanguage.locale_key')}]) }}" title="@lang('multilanguage::multilanguage.actions.manage')" data-toggle="tooltip" data-placement="top"><i class="glyphicon glyphicon-list-alt"></i></a>
							<a href="{{ action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getLanguageDelete', [$locale->{config('multilanguage.locale_key')}]) }}" title="@lang('multilanguage::multilanguage.actions.delete')" data-toggle="tooltip" data-placement="top"><i class="glyphicon glyphicon-trash"></i></a>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
        </div>
    </div>

@stop