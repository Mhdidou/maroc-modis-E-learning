<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Lie les tests Pest des dossiers Feature/Unit à la TestCase de l'application.
| Les tests basés sur des classes (PHPUnit) continuent de fonctionner en
| parallèle.
|
*/

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');
