<?php

namespace Tests\Unit\ContentManagement\Rules;

use App\Features\ContentManagement\Rules\ValidScheduleDate;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ValidScheduleDateTest extends TestCase
{
    /**
     * Prueba que una fecha futura (10 minutos adelante) pasa la validación.
     */
    public function test_future_date_passes(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();
        $futureDate = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

        // Act
        $result = $rule->passes('scheduled_for', $futureDate);

        // Assert
        $this->assertTrue($result, 'La fecha 10 minutos en el futuro debería pasar la validación');
    }

    /**
     * Prueba que una fecha pasada falla la validación.
     */
    public function test_past_date_fails(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();
        $pastDate = Carbon::now()->subMinutes(10)->format('Y-m-d H:i:s');

        // Act
        $result = $rule->passes('scheduled_for', $pastDate);

        // Assert
        $this->assertFalse($result, 'La fecha 10 minutos en el pasado debería fallar la validación');
    }

    /**
     * Prueba que una fecha a menos de 5 minutos en el futuro falla la validación.
     * Requisito: Debe ser al menos 5 minutos en el futuro.
     */
    public function test_date_less_than_5_minutes_fails(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();
        $tooSoonDate = Carbon::now()->addMinutes(2)->format('Y-m-d H:i:s');

        // Act
        $result = $rule->passes('scheduled_for', $tooSoonDate);

        // Assert
        $this->assertFalse($result, 'La fecha a solo 2 minutos en el futuro debería fallar la validación (se requieren mínimo 5 minutos)');
    }

    /**
     * Prueba que una fecha a más de 1 año (365 días) en el futuro falla la validación.
     * Requisito: No puede ser más de 1 año en el futuro.
     */
    public function test_date_more_than_1_year_fails(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();
        $tooFarDate = Carbon::now()->addDays(400)->format('Y-m-d H:i:s');

        // Act
        $result = $rule->passes('scheduled_for', $tooFarDate);

        // Assert
        $this->assertFalse($result, 'La fecha 400 días en el futuro debería fallar la validación (se permite un máximo de 365 días)');
    }

    /**
     * Prueba que el mensaje de error es descriptivo para fechas muy pronto.
     */
    public function test_error_message_is_descriptive_for_too_soon(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();
        $tooSoonDate = Carbon::now()->addMinutes(2)->format('Y-m-d H:i:s');

        // Act - activar fallo de validación por "too soon"
        $rule->passes('scheduled_for', $tooSoonDate);
        $message = $rule->message();

        // Assert
        $this->assertIsString($message, 'El mensaje de error debería ser una cadena de texto');
        $this->assertStringContainsString('5 minutes', $message, 'El mensaje de error debería mencionar un mínimo de 5 minutos');
        $this->assertStringContainsString('future', $message, 'El mensaje de error debería aclarar que debe ser en el futuro');
    }

    /**
     * Prueba que el mensaje de error es descriptivo para fechas muy lejanas.
     */
    public function test_error_message_is_descriptive_for_too_late(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();
        $tooLateDate = Carbon::now()->addDays(400)->format('Y-m-d H:i:s');

        // Act - activar fallo de validación por "too late"
        $rule->passes('scheduled_for', $tooLateDate);
        $message = $rule->message();

        // Assert
        $this->assertIsString($message, 'El mensaje de error debería ser una cadena de texto');
        $this->assertStringContainsString('1 year', $message, 'El mensaje de error debería mencionar un máximo de 1 año');
    }

    /**
     * Prueba de condición límite: exactamente 5 minutos en el futuro debería pasar.
     */
    public function test_exactly_5_minutes_passes(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();
        $exactlyFiveMinutes = Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s');

        // Act
        $result = $rule->passes('scheduled_for', $exactlyFiveMinutes);

        // Assert
        $this->assertTrue($result, 'La fecha exactamente 5 minutos en el futuro debería pasar la validación');
    }

    /**
     * Prueba de condición límite: exactamente 1 año (365 días) en el futuro debería pasar.
     */
    public function test_exactly_1_year_passes(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();
        $exactlyOneYear = Carbon::now()->addDays(365)->format('Y-m-d H:i:s');

        // Act
        $result = $rule->passes('scheduled_for', $exactlyOneYear);

        // Assert
        $this->assertTrue($result, 'La fecha exactamente 365 días en el futuro debería pasar la validación');
    }

    /**
     * Prueba que un formato de fecha inválido se maneja correctamente.
     */
    public function test_invalid_date_format_fails(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();
        $invalidDate = 'not-a-date';

        // Act
        $result = $rule->passes('scheduled_for', $invalidDate);

        // Assert
        $this->assertFalse($result, 'El formato de fecha inválido debería fallar la validación');
    }

    /**
     * Prueba que un valor nulo falla la validación.
     */
    public function test_null_value_fails(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();

        // Act
        $result = $rule->passes('scheduled_for', null);

        // Assert
        $this->assertFalse($result, 'El valor nulo debería fallar la validación');
    }

    /**
     * Prueba que una cadena vacía falla la validación.
     */
    public function test_empty_string_fails(): void
    {
        // Arrange
        $rule = new ValidScheduleDate();

        // Act
        $result = $rule->passes('scheduled_for', '');

        // Assert
        $this->assertFalse($result, 'La cadena vacía debería fallar la validación');
    }
}
