<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines Hungarian
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'A(z) :attribute-t el kell fogadni.',
    'accepted_if' => 'A(z) :attribute-t el kell fogadni, amikor :other :value.',
    'active_url' => 'A(z) :attribute nem érvényes URL.',
    'after' => 'A(z) :attribute-nak :date utáninak kell lennie.',
    'after_or_equal' => 'A(z) :attribute-nak :date utáninak vagy egyenlőnek kell lennie.',
    'alpha' => 'A(z) :attribute csak betűket tartalmazhat.',
    'alpha_dash' => 'A(z) :attribute csak betűket, számokat, kötőjeleket és aláhúzásokat tartalmazhat.',
    'alpha_num' => 'A(z) :attribute csak betűket és számokat tartalmazhat.',
    'array' => 'A(z) :attribute tömbnek kell lennie.',
    'ascii' => 'A(z) :attribute csak egysoros byte-ból álló alfanumerikus karaktereket és szimbólumokat tartalmazhat.',
    'before' => 'A(z) :attribute-nak :date előttinek kell lennie.',
    'before_or_equal' => 'A(z) :attribute-nak :date előttinek vagy egyenlőnek kell lennie.',
    'between' => [
        'array' => 'A(z) :attribute-nak :min és :max elem között kell lennie.',
        'file' => 'A(z) :attribute-nak :min és :max kilobájt között kell lennie.',
        'numeric' => 'A(z) :attribute-nak :min és :max között kell lennie.',
        'string' => 'A(z) :attribute-nak :min és :max karakter között kell lennie.',
    ],
    'boolean' => 'A(z) :attribute mezőnek igaznak vagy hamisnak kell lennie.',
    'confirmed' => 'A(z) :attribute megerősítése nem egyezik.',
    'current_password' => 'A(z) jelszó helytelen.',
    'date' => 'A(z) :attribute nem érvényes dátum.',
    'date_equals' => 'A(z) :attribute-nak egyenlőnek kell lennie :date.',
    'date_format' => 'A(z) :attribute nem felel meg a következő formátumnak: :format.',
    'decimal' => 'A(z) :attribute-nak :decimal tizedesjegynek kell lennie.',
    'declined' => 'A(z) :attribute-t el kell utasítani.',
    'declined_if' => 'A(z) :attribute-t el kell utasítani, amikor :other :value.',
    'different' => 'A(z) :attribute-nak és :other-nak különbözőnek kell lennie.',
    'digits' => 'A(z) :attribute-nak :digits számjegynek kell lennie.',
    'digits_between' => 'A(z) :attribute-nak :min és :max számjegy között kell lennie.',
    'dimensions' => 'A(z) :attribute érvénytelen képméretekkel rendelkezik.',
    'distinct' => 'A(z) :attribute mezőnek duplikált értéke van.',
    'doesnt_end_with' => 'A(z) :attribute nem végződhet a következőkkel: :values.',
    'doesnt_start_with' => 'A(z) :attribute nem kezdődhet a következőkkel: :values.',
    'email' => 'A(z) :attribute-nak érvényes e-mail címnek kell lennie.',
    'ends_with' => 'A(z) :attribute a következőkkel kell végződnie: :values.',
    'enum' => 'A(z) kiválasztott :attribute érvénytelen.',
    'exists' => 'A(z) kiválasztott :attribute érvénytelen.',
    'file' => 'A(z) :attribute-nak fájlnak kell lennie.',
    'filled' => 'A(z) :attribute mezőnek értéket kell tartalmaznia.',
    'gt' => [
        'array' => 'A(z) :attribute-nak több mint :value elemnek kell lennie.',
        'file' => 'A(z) :attribute-nak nagyobbnak kell lennie, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute-nak nagyobbnak kell lennie, mint :value.',
        'string' => 'A(z) :attribute-nak nagyobbnak kell lennie, mint :value karakter.',
    ],
    'gte' => [
        'array' => 'A(z) :attribute-nak legalább :value elemnek kell lennie.',
        'file' => 'A(z) :attribute-nak nagyobbnak vagy egyenlőnek kell lennie, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute-nak nagyobbnak vagy egyenlőnek kell lennie, mint :value.',
        'string' => 'A(z) :attribute-nak nagyobbnak vagy egyenlőnek kell lennie, mint :value karakter.',
    ],
    'image' => 'A(z) :attribute-nak képfájlnak kell lennie.',
    'in' => 'A(z) kiválasztott :attribute érvénytelen.',
    'in_array' => 'A(z) :attribute mezőnek nem léteznie kell a :other mezőben.',
    'integer' => 'A(z) :attribute-nak egész számnak kell lennie.',
    'ip' => 'A(z) :attribute-nak érvényes IP-címnek kell lennie.',
    'ipv4' => 'A(z) :attribute-nak érvényes IPv4-címnek kell lennie.',
    'ipv6' => 'A(z) :attribute-nak érvényes IPv6-címnek kell lennie.',
    'json' => 'A(z) :attribute-nak érvényes JSON-stringnek kell lennie.',
    'lowercase' => 'A(z) :attribute-nak kisbetűsnek kell lennie.',
    'lt' => [
        'array' => 'A(z) :attribute-nak kevesebb mint :value elemnek kell lennie.',
        'file' => 'A(z) :attribute-nak kevesebb mint :value kilobájt méretűnek kell lennie.',
        'numeric' => 'A(z) :attribute-nak kevesebb mint :value-nak kell lennie.',
        'string' => 'A(z) :attribute-nak kevesebb mint :value karakternek kell lennie.',
    ],
    'lte' => [
        'array' => 'A(z) :attribute-nak nem lehet több mint :value eleme.',
        'file' => 'A(z) :attribute-nak kevesebbnek vagy egyenlőnek kell lennie, mint :value kilobájt.',
        'numeric' => 'A(z) :attribute-nak kevesebbnek vagy egyenlőnek kell lennie, mint :value.',
        'string' => 'A(z) :attribute-nak kevesebbnek vagy egyenlőnek kell lennie, mint :value karakternek.',
    ],
    'mac_address' => 'A(z) :attribute-nak érvényes MAC-címnek kell lennie.',
    'max' => [
        'array' => 'A(z) :attribute-nak nem lehet több mint :max eleme.',
        'file' => 'A(z) :attribute-nak nem lehet nagyobb, mint :max kilobájt.',
        'numeric' => 'A(z) :attribute-nak nem lehet nagyobb, mint :max.',
        'string' => 'A(z) :attribute-nak nem lehet nagyobb, mint :max karakter.',
    ],
    'max_digits' => 'A(z) :attribute-nak nem lehet több mint :max számjegye.',
    'mimes' => 'A(z) :attribute-nak a következő típusú fájlnak kell lennie: :values.',
    'mimetypes' => 'A(z) :attribute-nak a következő típusú fájlnak kell lennie: :values.',
    'min' => [
        'array' => 'A(z) :attribute-nak legalább :min eleme kell legyen.',
        'file' => 'A(z) :attribute-nak legalább :min kilobájt méretűnek kell lennie.',
        'numeric' => 'A(z) :attribute-nak legalább :min-nak kell lennie.',
        'string' => 'A(z) :attribute-nak legalább :min karakternek kell lennie.',
    ],
    'min_digits' => 'A(z) :attribute-nak legalább :min számjegye kell legyen.',
    'missing' => 'A(z) :attribute mezőnek hiányoznia kell.',
    'missing_if' => 'A(z) :attribute mezőnek hiányoznia kell, amikor :other :value.',
    'missing_unless' => 'A(z) :attribute mezőnek hiányoznia kell, kivéve, ha :other :value.',
    'missing_with' => 'A(z) :attribute mezőnek hiányoznia kell, amikor :values jelen van.',
    'missing_with_all' => 'A(z) :attribute mezőnek hiányoznia kell, amikor :values jelen van.',
    'multiple_of' => 'A(z) :attribute-nak :value többszöröse kell legyen.',
    'not_in' => 'A(z) kiválasztott :attribute érvénytelen.',
    'not_regex' => 'A(z) :attribute formátuma érvénytelen.',
    'numeric' => 'A(z) :attribute-nak számnak kell lennie.',
    'password' => [
        'letters' => 'A(z) :attribute-nak tartalmaznia kell legalább egy betűt.',
        'mixed' => 'A(z) :attribute-nak tartalmaznia kell legalább egy nagybetűt és egy kisbetűt.',
        'numbers' => 'A(z) :attribute-nak tartalmaznia kell legalább egy számot.',
        'symbols' => 'A(z) :attribute-nak tartalmaznia kell legalább egy szimbólumot.',
        'uncompromised' => 'A(z) megadott :attribute megjelent egy adatlopás során. Kérjük, válasszon egy másik :attribute-t.',
    ],
    'present' => 'A(z) :attribute mezőnek jelen kell lennie.',
    'prohibited' => 'A(z) :attribute mező tilos.',
    'prohibited_if' => 'A(z) :attribute mező tilos, amikor :other :value.',
    'prohibited_unless' => 'A(z) :attribute mező tilos, kivéve, ha :other :value.',
    'prohibits' => 'A(z) :attribute mező megtiltja :other jelenlétét.',
    'regex' => 'A(z) :attribute formátuma érvénytelen.',
    'required' => 'A(z) :attribute mező kötelező.',
    'required_array_keys' => 'A(z) :attribute mezőnek tartalmaznia kell bejegyzéseket: :values.',
    'required_if' => 'A(z) :attribute mező kötelező, amikor :other :value.',
    'required_if_accepted' => 'A(z) :attribute mező kötelező, amikor :other elfogadva van.',
    'required_unless' => 'A(z) :attribute mező kötelező, kivéve, ha :other :values között van.',
    'required_with' => 'A(z) :attribute mező kötelező, amikor :values jelen van.',
    'required_with_all' => 'A(z) :attribute mező kötelező, amikor :values jelen van.',
    'required_without' => 'A(z) :attribute mező kötelező, amikor :values nincs jelen.',
    'required_without_all' => 'A(z) :attribute mező kötelező, amikor egyik :values sincs jelen.',
    'same' => 'A(z) :attribute és :other mezőknek meg kell egyezniük.',
    'size' => [
        'array' => 'A(z) :attribute mezőnek :size elemet kell tartalmaznia.',
        'file' => 'A(z) :attribute méretének :size kilobájt kell lennie.',
        'numeric' => 'A(z) :attribute-nak :size-nak kell lennie.',
        'string' => 'A(z) :attribute-nak :size karakternek kell lennie.',
    ],
    'starts_with' => 'A(z) :attribute-nak az alábbiak egyikével kell kezdődnie: :values.',
    'string' => 'A(z) :attribute-nak szövegnek kell lennie.',
    'timezone' => 'A(z) :attribute-nak érvényes időzónának kell lennie.',
    'unique' => 'A(z) :attribute már foglalt.',
    'uploaded' => 'A(z) :attribute feltöltése nem sikerült.',
    'uppercase' => 'A(z) :attribute-nak nagybetűsnek kell lennie.',
    'url' => 'A(z) :attribute-nak érvényes URL-nek kell lennie.',
    'ulid' => 'A(z) :attribute-nak érvényes ULID-nek kell lennie.',
    'uuid' => 'A(z) :attribute-nak érvényes UUID-nak kell lennie.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'comment' => 'Megjegyzés',
        'cursor' => 'Kurzor',
        'dir' => 'Irány',
        'email' => 'E-mail cím',
        'from_email' => 'Feladó e-mail',
        'from_name' => 'Feladó név',
        'id' => 'Id',
        'id_token' => 'Id token',
        'images' => 'Képek',
        'images.*' => 'Kép',
        'latitude' => 'Szélesség',
        'limit' => 'Limit',
        'longitude' => 'Hosszúság',
        'message' => 'Üzenet',
        'name' => 'Név',
        'order_start' => 'Rendelés kezdete',
        'page' => 'Oldal',
        'password' => 'Jelszó',
        'per_page' => 'Oldalankénti megjelenítés',
        'radius_km' => 'Sugár km',
        'role' => 'Szerep',
        'sort' => 'Rendezés',
        'subject' => 'Tárgy',
        'token' => 'Token',
        'type' => 'Típus',
        'password_confirmation' => 'Jelszó megerősítése',
    ],


];
