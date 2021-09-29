<?php

namespace App\SonarApi\Mutations;

use App\SonarApi\Mutations\Inputs\Input;
use GraphQL\Variable;

abstract class BaseMutation implements Mutation
{
    public function query(): \GraphQL\Mutation
    {
        $variables = $arguments = [];
        foreach (\get_object_vars($this) as $var => $value) {
            if ($value instanceof Input) {
                $variables[] = new Variable($var, $value->typeName());
            } else {
                $variables[] = new Variable(
                    $var,
                    is_int($value) ? 'Int' : 'String'
                );
            }
            $arguments[$var] = '$'.$var;
        }

        return (new \GraphQL\Mutation($this->name()))
            ->setVariables($variables)
            ->setArguments($arguments)
            ->setSelectionSet(
            $this->returnResource()
                ? ($this->returnResource())::graphQLQuery(false)
                : []
            );
    }

    public function variables(): array
    {
        $variables = [];
        foreach (\get_object_vars($this) as $var => $value) {
            if ($value instanceof Input) {
                $variables[$var] = $value->toArray();
            } else {
                $variables[$var] = $value;
            }
        }
        return $variables;
    }

    /**
     * Return name of the Sonar GraphQL mutation.
     */
    public function name(): string
    {
        return \lcfirst(\substr(static::class, \strrpos(static::class, '\\')+1));
    }

    abstract public function returnResource(): ?string;
}