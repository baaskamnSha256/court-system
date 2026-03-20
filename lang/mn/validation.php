<?php

return [
    'required' => ':attribute заавал бөглөнө.',
    'required_with' => ':values талбарт утга байгаа тул :attribute заавал бөглөнө.',

    'string' => ':attribute талбар текст байх ёстой.',
    'integer' => ':attribute талбар бүхэл тоо байх ёстой.',
    'array' => ':attribute талбар жагсаалт байх ёстой.',
    'date' => ':attribute талбар огноо байх ёстой.',

    'min' => [
        'numeric' => ':attribute талбар хамгийн багадаа :min байх ёстой.',
        'file' => ':attribute талбар хамгийн багадаа :min килобайт байх ёстой.',
        'string' => ':attribute талбар хамгийн багадаа :min тэмдэгт байх ёстой.',
        'array' => ':attribute талбар хамгийн багадаа :min утгатай байх ёстой.',
    ],

    'max' => [
        'numeric' => ':attribute талбар хамгийн ихдээ :max байх ёстой.',
        'file' => ':attribute талбар хамгийн ихдээ :max килобайт байх ёстой.',
        'string' => ':attribute талбар хамгийн ихдээ :max тэмдэгт байх ёстой.',
        'array' => ':attribute талбар хамгийн ихдээ :max утгатай байх ёстой.',
    ],

    'in' => 'Сонгосон :attribute буруу байна.',
    'exists' => 'Сонгосон :attribute буруу байна.',

    'custom' => [
        'preventive_measure.*.in' => 'Таслан сэргийлэх арга хэмжээний сонголт буруу байна. Зөвхөн жагсаалтаас сонгоно уу.',
    ],

    'attributes' => [
        'case_no' => 'Хэргийн дугаар',
        'hearing_state' => 'Хурлын төлөв',
        'hearing_date' => 'Хурлын огноо',
        'hour' => 'Цаг',
        'minute' => 'Минут',
        'courtroom' => 'Танхим',
        'presiding_judge_id' => 'Даргалагч шүүгч',
        'member_judge_1_id' => 'Гишүүн шүүгч 1',
        'member_judge_2_id' => 'Гишүүн шүүгч 2',
        'defendant_names' => 'Шүүгдэгч',
        'defendant_names.*' => 'Шүүгдэгч',
        'preventive_measure' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.*' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.0' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.1' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.2' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.3' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.4' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.5' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.6' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.7' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.8' => 'Таслан сэргийлэх арга хэмжээ',
        'preventive_measure.9' => 'Таслан сэргийлэх арга хэмжээ',
        'prosecutor_ids' => 'Улсын яллагч',
        'prosecutor_ids.*' => 'Улсын яллагч',
        'prosecutor_id' => 'Улсын яллагч',
    ],
];

