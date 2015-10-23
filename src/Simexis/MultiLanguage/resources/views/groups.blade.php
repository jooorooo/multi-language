@extends('multilanguage::layout.master')

@section('content')

	<div class="page-header">
		<div class="row">
			<div class="col-lg-11">
				<h1>@lang('multilanguage::multilanguage.title')</h1>
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
						<th class="name">@lang('multilanguage::multilanguage.entries.groupname')</th>
						<th class="lprogress">@lang('multilanguage::multilanguage.entries.complete')</th>
						<th class="actions">@lang('multilanguage::multilanguage.actions.actions')</th>
					</tr>
				</thead>
				<tbody>
					@foreach($groups AS $group)
					<tr>
						<td>{{ $group }}</td>
						<td>
							<div class="progress">
								<div class="progress-bar" role="progressbar" aria-valuenow="{{ $progress[$group] }}" aria-valuemin="0" aria-valuemax="100" style="width: {{ $progress[$group] < 10 ? 10 : $progress[$group] }}%;">
								{{ $progress[$group] }}%
								</div>
							</div>
						</td>
						<td class="text-right">
							<a href="{{ action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getView', [$locale->{config('multilanguage.locale_key')}, $group]) }}" title="@lang('multilanguage::multilanguage.actions.manage')" data-toggle="tooltip" data-placement="top"><i class="glyphicon glyphicon-list-alt"></i></a>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
        </div>
    </div>

@stop