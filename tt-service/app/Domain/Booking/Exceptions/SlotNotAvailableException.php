<?php

namespace App\Domain\Booking\Exceptions;

use RuntimeException;

/**
 * Выбрасывается когда запрошенный слот уже занят.
 * Обрабатывается в контроллере/handler — возвращает 409 Conflict.
 */
class SlotNotAvailableException extends RuntimeException {}
