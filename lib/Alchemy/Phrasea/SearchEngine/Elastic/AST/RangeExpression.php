<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class RangeExpression extends Node
{
    private $field;
    private $lower_bound;
    private $lower_inclusive;
    private $higher_bound;
    private $higher_inclusive;

    public static function lessThan(Field $field, $bound)
    {
        return new self($field, $bound, false);
    }

    public static function lessThanOrEqual(Field $field, $bound)
    {
        return new self($field, $bound, true);
    }

    public static function greaterThan(Field $field, $bound)
    {
        return new self($field, null, null, $bound, false);
    }

    public static function greaterThanOrEqual(Field $field, $bound)
    {
        return new self($field, null, null, $bound, true);
    }

    public function __construct(Field $field, $lb, $li = false, $hb = null, $hi = false)
    {
        $this->field = $field;
        $this->lower_bound = $lb;
        $this->lower_inclusive = $li;
        $this->higher_bound = $hb;
        $this->higher_inclusive = $hi;
    }

    public function buildQuery(QueryContext $context)
    {
        $params = array();
        if ($this->lower_bound !== null) {
            if ($this->lower_inclusive) {
                $params['lte'] = $this->lower_bound;
            } else {
                $params['lt'] = $this->lower_bound;
            }
        }
        if ($this->higher_bound !== null) {
            if ($this->higher_inclusive) {
                $params['gte'] = $this->higher_bound;
            } else {
                $params['gt'] = $this->higher_bound;
            }
        }

        $field = $context->normalizeField($this->field->getValue());
        $query = array();
        $query['range'][$field] = $params;

        return $query;
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        $string = '';
        if ($this->lower_bound !== null) {
            if ($this->lower_inclusive) {
                $string .= sprintf(' lte="%s"', $this->lower_bound);
            } else {
                $string .= sprintf(' lt="%s"', $this->lower_bound);
            }
        }
        if ($this->higher_bound !== null) {
            if ($this->higher_inclusive) {
                $string .= sprintf(' gte="%s"', $this->higher_bound);
            } else {
                $string .= sprintf(' gt="%s"', $this->higher_bound);
            }
        }

        return sprintf('<range:%s%s>', $this->field->getValue(), $string);
    }
}
