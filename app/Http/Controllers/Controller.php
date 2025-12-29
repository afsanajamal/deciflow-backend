<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="DeciFlow API",
 *     version="1.0.0",
 *     description="API documentation for DeciFlow - Purchase Request Approval System",
 *     @OA\Contact(
 *         email="support@deciflow.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token in the format: Bearer {token}"
 * )
 */
abstract class Controller
{
    //
}
