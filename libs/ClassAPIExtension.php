<?php

/**
 * ClassAPIExtension
 * 
 * Расширение API классов для PHP 8.0.0+
 * https://github.com/deathscore13/ClassAPIExtension
 */

interface ClassAPIExtensionResult
{
    public const apiNone       = null;  // Неизвестно
    public const apiSuccess    = 0;     // Успех
    public const apiNotExists  = 1;     // Функция не найдена
}

trait ClassAPIExtensionObject
{
    private ?int $apiResult = ClassAPIExtensionResult::apiNone;

    /**
     * Результат выполнения функции
     * 
     * @return int              ClassAPIExtensionResult::apiNone, ClassAPIExtensionResult::apiSuccess или ClassAPIExtensionResult::apiNotExists
     */
    public function apiResult(): int
    {
        return $this->apiResult;
    }

    /**
     * Костыль для параметров-ссылок
     * 
     * @param callable $name    Имя функции (первый параметр $this)
     * @param mixed &...$args   Входящие аргументы, в которых работают ссылки, в отличие от магического метода __call()
     * 
     * @return mixed            Возвращаемое значение функции
     */
    public function apiExec(callable $name, mixed &...$args): mixed
    {
        if (function_exists($name = '\\'.self::class.'APIExtension\\'.$name))
        {
            $this->apiResult = ClassAPIExtensionResult::apiSuccess;
            return $name($this, ...$args);
        }
        $this->apiResult = ClassAPIExtensionResult::apiNotExists;
        return false;
    }

    public function __call(string $name, array $args): mixed
    {
        if (function_exists($name = '\\'.self::class.'APIExtension\\'.$name))
        {
            $this->apiResult = ClassAPIExtensionResult::apiSuccess;
            return $name($this, ...$args);
        }
        $this->apiResult = ClassAPIExtensionResult::apiNotExists;
        return false;
    }
}

trait ClassAPIExtensionStatic
{
    private static ?int $apiResultStatic = ClassAPIExtensionResult::apiNone;

    /**
     * Результат статичного выполнения функции
     * 
     * @return int              ClassAPIExtensionResult::apiNone, ClassAPIExtensionResult::apiSuccess или ClassAPIExtensionResult::apiNotExists
     */
    public static function apiResultStatic(): int
    {
        return ClassAPIExtensionResult::$apiResultStatic;
    }

    /**
     * Статический костыль для параметров-ссылок
     * 
     * @param callable $name    Имя функции (первый параметр self)
     * @param mixed &...$args   Входящие аргументы, в которых работают ссылки, в отличие от магического метода __callStatic()
     * 
     * @return mixed            Возвращаемое значение функции
     */
    public static function apiExecStatic(callable $name, mixed &...$args): mixed
    {
        if (function_exists($name = '\\'.self::class.'APIExtension\\'.$name))
        {
            $this->apiResult = ClassAPIExtensionResult::apiSuccess;
            return $name(self::class, ...$args);
        }
        $this->apiResult = ClassAPIExtensionResult::apiNotExists;
        return false;
    }

    public static function __callStatic(string $name, array $args): mixed
    {
        if (function_exists($name = '\\'.self::class.'APIExtension\\'.$name))
        {
            self::$apiResultStatic = ClassAPIExtensionResult::apiSuccess;
            return $name(self::class, ...$args);
        }
        self::$apiResultStatic = ClassAPIExtensionResult::apiNotExists;
        return false;
    }
}

trait ClassAPIExtensionVars
{
    private array $apiVars = [];

    public function __set(string $name, mixed $value): void
    {
        $this->apiVars[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->apiVars[$name];
    }

    public function __isset(string $name): bool
    {
        return isset($this->apiVars[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->apiVars[$name]);
    }
}

trait ClassAPIExtension
{
    use ClassAPIExtensionObject, ClassAPIExtensionStatic, ClassAPIExtensionVars;
}