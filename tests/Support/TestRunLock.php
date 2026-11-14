<?php

declare(strict_types=1);

namespace Logbook\Tests\Support;

use RuntimeException;

/**
 * One PHPUnit run at a time per checkout (spec.md §9 *Test suite only*,
 * K197, #619). Every run deletes and re-migrates the test database and
 * shares var/cache, so a second run started while one is going would wipe
 * the first one's data. The bootstrap takes an exclusive lock on
 * var/testing.lock before it touches either; a second run says it is
 * waiting, once, and blocks until the first finishes. The operating system
 * frees the lock when a run ends, however it ends.
 */
final class TestRunLock
{
    public const string WAITING = "Waiting for another test run in this checkout to finish…\n";

    /**
     * The open lock file, kept for the life of the process: closing it (or
     * letting it be collected) would free the lock mid-run.
     *
     * @var resource|null
     */
    private static $handle = null;

    /**
     * Take the lock on $path, waiting for it if another run holds it.
     *
     * @param resource $stderr where the waiting message goes
     */
    public static function acquire(string $path, $stderr = STDERR): void
    {
        $handle = fopen($path, 'c');
        if ($handle === false) {
            throw new RuntimeException('Cannot open the test run lock ' . $path);
        }
        // Try without blocking first, so the message shows only when there is a wait.
        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fwrite($stderr, self::WAITING);
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock ' . $path);
            }
        }
        self::$handle = $handle;
    }

    /** Whether this process holds the lock (taken and not yet freed by exit). */
    public static function isHeld(): bool
    {
        return self::$handle !== null;
    }
}
