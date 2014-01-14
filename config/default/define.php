<?php
/**
 * Services
 */
$di->set('web_request', $di->lazyNew('Aura\Web\Request'));
$di->set('web_response', $di->lazyNew('Aura\Web\Response'));
$di->set('web_router', $di->lazyNew('Aura\Router\Router'));


/**
 * Thedigital\Rest\RestController
 */
$di->params['Thedigital\Rest\RestController'] = array(
    'request'   => $di->lazyGet('web_request'),
    'response'  => $di->lazyGet('web_response'),
    'router'    => $di->lazyGet('web_router')
);
