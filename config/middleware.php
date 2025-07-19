<?php

// Slim middlewares are LIFO (last in, first out) so when responding, the order is backwards
// https://samuel-gfeller.ch/docs/Slim-Middlewares#order-of-execution
return function (Slim\App $app) {
    $app->addBodyParsingMiddleware();

    // Add new middlewares here

    $app->addRoutingMiddleware();
};
