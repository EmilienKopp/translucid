<?php

namespace Splitstack\EnumFriendly\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Splitstack\EnumFriendly\EnumFriendlyServiceProvider;

class CommandTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();

    if (!File::exists(app_path('Enums'))) {
      File::makeDirectory(app_path('Enums'), 0777, true);
    }
  }

  protected function tearDown(): void
  {
    File::deleteDirectory(app_path('Enums'));
    parent::tearDown();
  }

  protected function getPackageProviders($app)
  {
    return [EnumFriendlyServiceProvider::class];
  }

  public function test_it_can_create_basic_enum()
  {
    $enumName = 'Status';
    $values = ['PENDING', 'ACTIVE', 'INACTIVE'];

    Artisan::call('split:enum', [
      'name' => $enumName,
      'values' => $values
    ]);

    $filePath = app_path("Enums/{$enumName}.php");

    $this->assertTrue(File::exists($filePath));
    $content = File::get($filePath);

    $this->assertStringContainsString("enum {$enumName}", $content);
    $this->assertStringContainsString('namespace App\Enums;', $content);
    $this->assertStringContainsString('use Splitstack\EnumFriendly\Traits\ExtendedEnum;', $content);

    foreach ($values as $value) {
      $this->assertStringContainsString("case {$value};", $content);
    }
  }

  public function test_it_can_create_string_backed_enum()
  {
    Artisan::call('split:enum', [
      'name' => 'PaymentStatus',
      'values' => ['PENDING:pending', 'PAID:paid'],
      '--type' => 'string'
    ]);

    $content = File::get(app_path('Enums/PaymentStatus.php'));

    $this->assertStringContainsString("enum PaymentStatus: string", $content);
    $this->assertStringContainsString("case PENDING = 'pending';", $content);
    $this->assertStringContainsString("case PAID = 'paid';", $content);
  }

  public function test_it_can_create_int_backed_enum()
  {
    Artisan::call('split:enum', [
      'name' => 'Priority',
      'values' => ['LOW:0', 'MEDIUM:5', 'HIGH:10'],
      '--type' => 'int'
    ]);

    $content = File::get(app_path('Enums/Priority.php'));

    $this->assertStringContainsString("enum Priority: int", $content);
    $this->assertStringContainsString("case LOW = 0;", $content);
    $this->assertStringContainsString("case MEDIUM = 5;", $content);
    $this->assertStringContainsString("case HIGH = 10;", $content);
  }

  public function test_it_converts_case_names_to_uppercase()
  {
    Artisan::call('split:enum', [
      'name' => 'UserType',
      'values' => ['admin', 'moderator', 'user'],
      '--upper' => true
    ]);

    $content = File::get(app_path('Enums/UserType.php'));

    $this->assertStringContainsString("case ADMIN;", $content);
    $this->assertStringContainsString("case MODERATOR;", $content);
    $this->assertStringContainsString("case USER;", $content);
  }

  public function test_it_converts_case_names_to_uppercase_with_shorthand()
  {
    Artisan::call('split:enum', [
      'name' => 'UserType',
      'values' => ['admin', 'moderator', 'user'],
      '-u' => true
    ]);

    $content = File::get(app_path('Enums/UserType.php'));

    $this->assertStringContainsString("case ADMIN;", $content);
    $this->assertStringContainsString("case MODERATOR;", $content);
    $this->assertStringContainsString("case USER;", $content);
  }

  public function test_it_throws_exception_for_invalid_int_values()
  {
    $this->expectException(\InvalidArgumentException::class);

    Artisan::call('split:enum', [
      'name' => 'InvalidEnum',
      'values' => ['TEST:not-a-number'],
      '--type' => 'int'
    ]);
  }

  public function test_it_handles_mixed_value_formats()
  {
    Artisan::call('split:enum', [
      'name' => 'MixedFormat',
      'values' => ['SIMPLE', 'MAPPED:custom'],
      '--type' => 'string'
    ]);

    $content = File::get(app_path('Enums/MixedFormat.php'));

    $this->assertStringContainsString("case SIMPLE = 'SIMPLE';", $content);
    $this->assertStringContainsString("case MAPPED = 'custom';", $content);
  }
}
