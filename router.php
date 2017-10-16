<?php
require 'Dispatcher.php';
// Dictionary of supported filters and their patterns
const FILTERS = array(
    'c' => array ('title' => 'crop', 'pattern' => '{w|int}x{h|int}[&{x|int},{y|int}]'),
    'r' => array ('title' => 'resize', 'pattern' => '{w}x{h}'),
);

$dispatcher  = new UriDispatcher(FILTERS);
$filtersData = $dispatcher->parseFilters();
$imageData   = array('id' => $dispatcher->id, 'filters' => $filtersData);
// Call image -> show($data);