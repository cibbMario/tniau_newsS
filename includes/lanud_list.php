<?php
$LANUD_OPTIONS = [
    'Lanud Halim Perdanakusuma',
    'Lanud Roesmin Nurjadin',
    'Lanud Supadio',
    'Lanud Atang Sendjaja',
    'Lanud Suryadarma',
    'Lanud Soewondo',
    'Lanud Sultan Iskandar Muda',
    'Lanud H. A. S. Hanandjoeddin',
    'Lanud Sutan Sjahrir',
    'Lanud Raja Haji Fisabilillah',
    'Lanud Raden Sadjad',
    'Lanud Sri Mulyono Herlambang',
    'Lanud Maimun Saleh',
    'Lanud Harry Hadisoemantri',
    'Lanud Pangeran M. Bun Yamin',
    'Lanud Sugiri Sukani',
    'Lanud Wiriadinata',
    'Lanud H. As Hanandjoeddin',
    'Lanud Abdullah Sanusi',
    'Lanud Iswahjudi',
    'Lanud Abdulrachman Saleh',
    'Lanud Sultan Hasanuddin',
    'Lanud Sam Ratulangi',
    'Lanud El Tari',
    'Lanud Dhamer',
    'Lanud Dumatubun',
    'Lanud Anang Busra',
    'Lanud Dhaan Jahja',
    'Lanud H. Aroeppala',
    'Lanud Iskandar',
    'Lanud Syamsudin Noor',
    'Lanud Zamrud',
    'Lanud I Gusti Ngurah Rai',
    'Lanud Silas Papare',
    'Lanud Manuhua',
    'Lanud Johannes Abraham Dimara',
    'Lanud Pattimura',
    'Lanud Adisutjipto',
    'Lanud Adi Soemarmo',
    'Lanud Sulaiman'
];

function render_lanud_select($name = 'wilayah', $selected = null, $attrs = '') {
    global $LANUD_OPTIONS;
    $selected = $selected ?? 'Lanud Atang Sendjaja';
    $html = "<select name=\"" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "\" $attrs>";
    foreach ($LANUD_OPTIONS as $opt) {
        $sel = ($selected === $opt) ? ' selected' : '';
        $html .= '<option value="' . e($opt) . '"' . $sel . '>' . e($opt) . '</option>';
    }
    $html .= '</select>';
    return $html;
}
