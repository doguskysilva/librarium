<?php

namespace App\Enums;

enum CollectionType: string
{
    case Editorial = 'editorial';
    case LiterarySeries = 'literary_series';
    case FictionalUniverse = 'fictional_universe';
    case AuthorBibliography = 'author_bibliography';
    case Thematic = 'thematic';
}
