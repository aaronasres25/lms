<?php

namespace App\Exceptions;

use Exception;

/** Business rule violation - equivalent of Odoo's UserError / ValidationError. */
class LmsException extends Exception
{
}
