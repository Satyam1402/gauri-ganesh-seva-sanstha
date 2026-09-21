<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A backup/restore request that cannot proceed for a reason the admin
 * should see verbatim (last valid backup, archive missing, unsupported
 * scope…). Never carries technical or secret detail.
 */
class BackupOperationException extends RuntimeException {}
