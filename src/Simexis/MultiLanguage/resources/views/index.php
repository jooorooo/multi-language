<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="csrf-token" content="<?= csrf_token() ?>" />

    <title>Translation Manager</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
    <script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet"/>
    <script src="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
    
    <link href="<?= action('\Simexis\MultiLanguage\Controllers\AssetController@getCss') ?>" rel="stylesheet">

</head>
<body>
<div style="width: 80%; margin: auto;">
    <h1>Translation Manager</h1>
    <p>Warning, translations are not visible until they are exported back to the app/lang file, using 'php artisan translation:export' command or publish button.</p>
    <div class="alert alert-success success-import" style="display:none;">
        <p>Done importing, processed <strong class="counter">N</strong> items! Reload this page to refresh the groups!</p>
    </div>
    <div class="alert alert-success success-find" style="display:none;">
        <p>Done searching for translations, found <strong class="counter">N</strong> items!</p>
    </div>
    <div class="alert alert-success success-publish" style="display:none;">
        <p>Done publishing the translations for group '<?= $group ?>'!</p>
    </div>
    <?php if(Session::has('successPublish')) : ?>
        <div class="alert alert-info">
            <?php echo Session::get('successPublish'); ?>
        </div>
    <?php endif; ?>
    <p>
        <?php if(!isset($group)) : ?>
        <form class="form-inline form-global-action" method="POST" action="<?= action('\Simexis\MultiLanguage\Controllers\MultilanguageController@postImport') ?>" data-remote="true" role="form">
            <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <select name="replace" class="form-control">
                <option value="append">Append new translations</option>
                <option value="replace">Replace existing translations</option>
                <option value="truncate">Truncate translations</option>
                <option value="clear">Clear non existings translations</option>
            </select>
            <button type="submit" class="btn btn-success"  data-disable-with="Loading..">Submit</button>
        </form>
        <?php endif; ?>
    </p>
    <form role="form">
        <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
        <div class="form-group">
            <select name="group" id="group" class="form-control group-select">
				<option value="<?= action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex') ?>">Choose a group</option>
                <?php foreach($groups as $key => $value): ?>
				<option value="<?= action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getView', [$key]) ?>"<?= $key == $group ? ' selected':'' ?>><?= $value ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <?php if($group): ?>

    <h4>Total: <?= $numTranslations ?>, changed: <?= $numChanged ?></h4>
    <table class="table">
        <thead>
        <tr>
            <th width="15%">Key</th>
            <th width="15%">Default</th>
            <?php foreach($locales as $locale): ?>
                <th><?= $locale ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>

        <?php foreach($translations as $key => $translation):  ?>
            <tr id="<?= $key ?>">
                <td><?= $key ?></td>
                <td><?= isset($defaults[$key]) ? $defaults[$key] : null ?></td>
                <?php foreach($locales as $locale => $title): ?>
                    <?php $t = isset($translation[$locale]) ? $translation[$locale] : null?>

                    <td>
                        <a href="#edit" class="editable status-<?= $t ? $t->locked : 0 ?> locale-<?= $locale ?>" data-locale="<?= $locale ?>" data-name="<?= $locale . "|" . $key ?>" id="username" data-type="textarea" data-pk="<?= $t ? $t->id : 0 ?>" data-url="<?= $editUrl ?>" data-title="Enter translation"><?= $t ? htmlentities($t->text, ENT_QUOTES, 'UTF-8', false) : '' ?></a>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>
    <?php else: ?>
        <p>Choose a group to display the group translations. If no groups are visisble, make sure you have run the migrations and imported the translations.</p>

    <?php endif; ?>
</div>

    <script src="<?= action('\Simexis\MultiLanguage\Controllers\AssetController@getJs') ?>"></script>
	
</body>
</html>
