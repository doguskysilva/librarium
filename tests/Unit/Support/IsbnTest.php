<?php

use App\Support\Isbn;

it('converts valid ISBN-10 values to their ISBN-13 equivalent', function () {
    expect(Isbn::toIsbn13('0-306-40615-2'))->toBe('9780306406157');
});

it('converts an ISBN-10 with an X check digit', function () {
    expect(Isbn::toIsbn13('0-8044-2957-X'))->toBe('9780804429573');
});

it('accepts a valid ISBN-13 and rejects invalid checksums', function () {
    expect(Isbn::toIsbn13('978-0-306-40615-7'))->toBe('9780306406157')
        ->and(Isbn::toIsbn13('9780306406158'))->toBeNull();
});

it('returns both equivalent formats for ISBN-10 and ISBN-13 inputs', function () {
    expect(Isbn::equivalents('0306406152'))->toBe([
        '9780306406157',
        '0306406152',
    ])->and(Isbn::equivalents('9780306406157'))->toBe([
        '9780306406157',
        '0306406152',
    ]);
});

it('does not infer an ISBN-10 equivalent for a 979 ISBN-13', function () {
    expect(Isbn::equivalents('9791090636071'))->toBe(['9791090636071']);
});
