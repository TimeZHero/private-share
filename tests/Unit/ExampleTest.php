<?php

test('that true is true', function () {
    expect(true)->toBeTrue();
});

it('uses hardened serialization defaults', function () {
    expect(config('cache.serializable_classes'))->toBeFalse()
        ->and(config('session.serialization'))->toBe('json');
});
