@extends('multilanguage::layout.master')

@section('content')

	<div class="page-header">
		<div class="row">
			<div class="col-lg-11">
				<h1>@lang('multilanguage::multilanguage.title2', ['lang' => $locale->name, 'group' => $group])</h1>
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
            
			<h4>@lang('multilanguage::multilanguage.total', ['total' => $numTranslations, 'changed' => $numChanged])</h4>
			<table class="table">
				<thead>
				<tr>
					<th width="15%">@lang('multilanguage::multilanguage.entries.key')</th>
					<th>@lang('multilanguage::multilanguage.entries.default_system')</th>
					<th><?= $locale->name ?></th>
				</tr>
				</thead>
				<tbody>

				@foreach($translations as $key => $translation)
					<tr id="{{ $key }}">
						<td>{{ $key }}</td>
						<td>{{ isset($defaults[$key]) ? $defaults[$key] : null }}</td>
						{!! ''; $t = isset($translation[$locale->{config('multilanguage.locale_key')}]) ? $translation[$locale->{config('multilanguage.locale_key')}] : null !!}
						<td>
							<a href="#edit" class="editable status-{{ $t ? $t->locked : 0 }} locale-{{ $locale->{config('multilanguage.locale_key')} }}" data-locale="{{ $locale->{config('multilanguage.locale_key')} }}" data-name="{{ $locale->{config('multilanguage.locale_key')} . "|" . $key }}" data-type="textarea" data-pk="{{ $t ? $t->id : 0 }}" data-url="{!! $editUrl !!}" data-title="@lang('multilanguage::multilanguage.entries.manage')" data-emptytext="@lang('multilanguage::multilanguage.entries.empty')"><?= $t ? htmlentities($t->text, ENT_QUOTES, 'UTF-8', false) : '' ?></a>
						</td>
					</tr>
				@endforeach

				</tbody>
			</table>
			
        </div>
    </div>

@stop