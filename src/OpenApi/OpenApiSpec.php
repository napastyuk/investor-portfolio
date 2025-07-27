<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Investor Portfolio API",
 *     version="1.0.0",
 *     description="Документация к REST API проекта Investor Portfolio"
 * )
 *
 * @OA\Server(
 *     url="http://localhost",
 *     description="Local server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
final class OpenApiSpec {}
