<?php

namespace Bnomei;

use AllowDynamicProperties;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Obj;
use Kirby\Toolkit\Str;
use Kirby\Uuid\Uuid;

/**
 * @property ?string $id
 * @property ?string $uuid
 * @property string $title
 * @property ?string $slug
 * @property ?string $model
 * @property ?string $template
 * @property ?int $num
 * @property ?array $content
 *
 * @method self id(string $id)
 * @method self uuid(string $uuid)
 * @method self title(string $title)
 * @method self slug(string $slug)
 * @method self model(string $model)
 * @method self template(string $template)
 * @method self num(?int $num)
 * @method self content(array $content)
 */
#[AllowDynamicProperties]
class APIRecord extends Obj
{
    public function __construct(
        array $data = [],
        array $map = [],
    ) {
        parent::__construct(
            get_object_vars($this)
        );
        $this->title(A::get($data, 'title')); // title and slug
        $this->num = null; // unlisted
        $this->model = null; // automatic matching to template
        $this->template = 'default';
        $this->uuid = Uuid::generate();

        foreach ($map as $property => $path) {
            $this->$property($this->resolveMap($data, $property, $path));
        }

        // uuid might be created in content. keep it in sync in two steps.
        if ($uuid = A::get($this->content, 'uuid')) {
            $this->uuid = $uuid;
        }
        $this->content['uuid'] = $this->uuid;
    }

    private function resolveMap(array $data, string $property, string|array|\Closure $path): mixed
    {
        if (is_string($path)) {
            return A::get($data, $path); // dot-notion support
        } elseif (is_array($path)) {
            $out = [];
            foreach ($path as $key => $value) {
                $out[$key] = $this->resolveMap($data, $key, $value);
            }

            return $out;
        } elseif ($path instanceof \Closure) {
            return $path($data);
        }

        return null; // @phpstan-ignore-line
    }

    public function __call(string $property, array $arguments): self
    {
        $value = $arguments[0] ?? null;

        // infer slug from title
        if ($property === 'title' && ! $this->get('slug')) {
            $this->slug = Str::slug(strval($value));
        }

        // infer model from template
        if ($property === 'template' && ! $this->get('model')) {
            $this->model = $value;
        }

        $this->$property = $value;

        return $this;
    }

    public function toArray(): array
    {
        $result = parent::toArray();
        ksort($result);

        return $result;
    }
}
