<?php
namespace App\Enum;

enum Role: string
{
    case Client = 'ROLE_CLIENT';
    case Manager = 'ROLE_MANAGER';
    case Admin = 'ROLE_ADMIN';
}