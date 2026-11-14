<?php

declare(strict_types=1);

namespace Logbook\Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * PHPStan is on 2.3 or later and Dependabot no longer holds it back
 * (K175, #620): the false errors 2.3 reported are answered by targeted
 * ignores in phpstan.neon.dist rather than by staying on 2.2.
 */
final class PhpStanVersionTest extends TestCase
{
    public function testTheLockHoldsPhpStanTwoPointThreeOrLater(): void
    {
        $lock = json_decode(self::read('composer.lock'), true);
        self::assertIsArray($lock);
        $version = null;
        foreach ((array) ($lock['packages-dev'] ?? []) as $package) {
            if (is_array($package) && ($package['name'] ?? null) === 'phpstan/phpstan') {
                $version = $package['version'] ?? null;
            }
        }
        self::assertIsString($version, 'phpstan/phpstan is a locked dev dependency');
        self::assertTrue(version_compare(ltrim($version, 'v'), '2.3.0', '>='), 'locked ' . $version);
    }

    public function testDependabotDoesNotIgnorePhpStan(): void
    {
        self::assertStringNotContainsString('dependency-name: phpstan/phpstan', self::read('.github/dependabot.yml'));
    }

    private static function read(string $file): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . '/' . $file);
        self::assertIsString($contents, $file);

        return $contents;
    }
}
