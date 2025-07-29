<?php declare(strict_types=1);

namespace App\Interface\Http;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Investor Portfolio API",
    description: "Документация к API"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
class OpenApiConfig {}
