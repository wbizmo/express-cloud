<?php

declare(strict_types=1);

namespace App\Exceptions\Sales;

use DomainException;

final class DuplicateIdempotencyKey extends DomainException {}
