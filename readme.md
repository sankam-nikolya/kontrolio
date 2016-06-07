# Kontrolio: keep your data under control.
Kontrolio is a simple standalone data validation library inspired by Laravel and Symfony.

## Setting up validator
Kontrolio's setup and validation API is pretty standard:
 
```php
$validator = new Validator($data, $rules, $messages);
$validator->validate();
```

Data here is supposed to be the key-value pairs—attributes and their values:

```php
$data = [
    'foo' => 'bar',
    'bar' => 'baz',
    'baz' => 'taz'
];
```

Validation rules can be set in three different formats:
1. Laravel-like string
2. Instances of the class based rules
3. Callables (closures or callbacks)

You can also mix instances and callables when you set multiple validation rules to a single attribute. Here's a simple example:

```php
$rules = [
    'one' => 'not_empty|length:5,15',
    'two' => new Email,
    'three' => function($value) {
        return $value === 'taz';
    },
    'four' => [
        function($value) {
            return is_numeric($value);
        },
        new GreaterThan(5),
    ]
];
```

When you set validation rules as string, validator will parse it to an ordinary array of rules before applying them to the attribute, so when you write `'some' => 'not_empty|length:5,15'`, it becomes

```php
'some' => [
    new NotEmpty,
    new Length(5, 15)
]
```

It's pretty straightforward but remember that all arguments you pass after semicolon (separating them by commas) become the arguments of the validation rule constructor.

When you set validation rule as callback, internally it is wrapped by special object called `Kontrolio\Rules\CallbackRuleWrapper` to keep consistency defined by `Kontrolio\Rules\RuleInterface` interface.

## Rule options
### Allowing empty value
A single rule validation can be skipped when the validated attribute value is empty. If you feel that you need this option, you instantiate a rule using named constructor `allowingEmptyValue()` or by calling `allowEmptyValue()` method on the already existing instance:

```php
'some' => [
    MyRule::allowingEmptyValue(),
    // (new MyRule)->allowEmptyValue()
]
```

### Skipping rule validation
When creating a new custom class based rule you might need an option to skip its validation based on some conditions. You can define the behavior using `canSkipValidation()` method:

```php

class MyRule extends AbstractRule
{
    public function canSkipValidation($input = null)
    {
        return $input === 'bar';
    }

    // ...
}

```
## Callable rules
Callable rule is nothing more than a closure or function that takes an attribute value and returns either boolean result of the validation or an options array equivalent to options provided by a class based validation rule:

```php
    'foo' => function($value) {
        return is_string($value);
    },
    'bar' => function($value) {
        return [
            // required when array
            'valid' => $value === 'taz',
            
            // optionals
            'name' => 'baz', // rule identifier
            'empty_allowed' => true, // allowing empty value
            'skip' => false // don't allow skipping current rule validation,
            'violations' => [] // rule violations
        ];
    }
```

## Custom rules
Of course you can create your custom rules. Just remember that each rule must be an instance of `Kontrolio\Rules\RuleInterface`, implement `isValid()` method and have an identifier. By default, identifiers are resolved by `Kontrolio\Rules\AbstractRule` and are based on the rule class name without namespace. However you can override this behavior if you wish overriding `getName()` method.

To let the validator know about your custom validation rules you need to manually add them by using `Kontrolio\Validator::extendAvailableRules()` static method as follows:

```php
Validator::extendAvailableRules([
   FirstRule::class,
   SecondRule::class
])
```

If you want to use custom identifiers you can do that as well:

```php
Validator::extendAvailableRules([
   'foo' => FirstRule::class,
   'bar' => SecondRule::class
])
```

## Bypassing attribute's value validation completely
It's not the same as using `allowEmptyValue()` or `canSkipValidation()` on a rule. With those you can skip _only a rule_. But you can also bypass a whole attribute by using `Kontrolio\Rules\Core\Sometimes` rule. `Sometimes` tells validator to bypass validation when the value of the attribute is null or empty. That's all. You can prepend `Sometimes` to the attribute's rules array or use its identifier in a ruleset string:

```php
$rules = [
    'one' => 'sometimes|length:5,15',
    // 'one' => [
    //     new Sometimes,
    //     new Length(5, 15)
    // ]
];
```

## Error messages and rule violations
Error messages has a single simple format you'll love:

```php
$messages = [
    'foo' => 'Foo cannot be null',
    'foo.length' => 'Wrong length of foo',
    'foo.length.min' => 'Foo is less than 3'
    // '[attribute].[rule].[violation]
];
```
Every message key can have three segments separated by dot. They are:
1. Attribute name
2. Validation rule identifier
3. Validation rule _violation_

With these options you can customize messages from the most general to the most specific. Each violation is set by the rule validating the attribute value. So when writing your own validation rule you may provide your own violations to provide customizability of the validation result and error messages.