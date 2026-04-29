<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-text-template.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Template;

use function array_merge;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function sprintf;
use function str_replace;

final class Template
{
    /**
     * @var string
     */
    private $template = '';

    /**
     * @var string
     */
    private $openDelimiter;

    /**
     * @var string
     */
    private $closeDelimiter;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $file = '', string $openDelimiter = '{', string $closeDelimiter = '}')
    {
        $this->setFile($file);

        $this->openDelimiter  = $openDelimiter;
        $this->closeDelimiter = $closeDelimiter;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setFile(string $file): void
    {
        $distFile = $file . '.dist';

        if (file_exists($file)) {
            $this->template = file_get_contents($file);
        } elseif (file_exists($distFile)) {
            $this->template = file_get_contents($distFile);
        } else {
            throw new InvalidArgumentException(
                sprintf(
                    'Failed to load template "%s"',
                    $file
                )
            );
        }
    }

    public function setVar(array $values, bool $merge = true): void
    {
        if (!$merge || empty($this->values)) {
            $this->values = $values;
        } else {
            $this->values = array_merge($this->values, $values);
        }
    }

    public function render(): string
    {
        $keys = [];

        foreach ($this->values as $key => $value) {
            $keys[] = $this->openDelimiter . $key . $this->closeDelimiter;
        }

        return str_replace($keys, $this->values, $this->template);
    }

    /**
     * @codeCoverageIgnore
     */
    public function renderTo(string $target): void
    {
        if (!file_put_contents($target, $this->render())) {
            throw new RuntimeException(
                sprintf(
                    'Writing rendered result to "%s" failed',
                    $target
                )
            );
        }
    }
}
