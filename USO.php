<?php
define('TEAM_NAME', 'Orléans');
define('TEAM_SLUG', 'orleans');

//define('TEAM_NAME', 'Auxerre');
//define('TEAM_SLUG', 'auxerre');

$data = shell_exec("curl -s https://www.matchendirect.fr/equipe/" . TEAM_SLUG . ".html | /usr/local/bin/pup '#livescore tr json{}'");
$data = json_decode($data, true);

$match_date_previous = '';
$match_team1_previous = '';
$match_score_previous = '';
$match_state_previous = '';
$match_team2_previous = '';
$match_date_next = '';
$match_team1_next = '';
$match_score_next = '';
$match_state_next = '';
$match_team2_next = '';

$found_played_game = false;
$rank = 0;

foreach ($data as $match) {
    if (@$match["children"][1]["text"] === "Equipe") {
        break;
    }

    //$match_time = $match["children"][0]["text"];
    $match_date = $match["children"][1]["text"];
    $match_team1 = $match["children"][2]["children"][0]["children"][0]["text"];
    $match_score = @$match["children"][2]["children"][0]["children"][1]["text"];
    $match_team2 = $match["children"][2]["children"][0]["children"][2]["text"];
    switch (@$match["children"][2]["children"][0]["children"][1]["class"]) {
        case 'lm3_score g':
            $match_state = 'Win';
            break;
        case 'lm3_score p':
            $match_state = 'Lose';
            break;
        case 'lm3_score n':
            $match_state = 'Tie';
            break;
        default:
            $match_state = '-';
            break;
    }

    if (!empty($match_score)) {
        $match_date_next = $match_date_previous;
        $match_team1_next = $match_team1_previous;
        $match_team2_next = $match_team2_previous;

        $match_date_previous = $match_date;
        $match_team1_previous = $match_team1;
        $match_score_previous = $match_score;
        $match_state_previous = $match_state;
        $match_team2_previous = $match_team2;

        $found_played_game = true;

        break;
    }

    $match_date_previous = $match_date;
    $match_team1_previous = $match_team1;
    $match_score_previous = $match_score;
    $match_state_previous = $match_state;
    $match_team2_previous = $match_team2;
}

foreach ($data as $match) {
    if (@$match["children"][2]["children"][0]["tag"] === "strong") {
        if (@$match["children"][1]["children"][0]["text"] === TEAM_NAME) {
            $rank = isset($match["children"][0]["children"][0]["text"])
                        ? (int)$match["children"][0]["children"][0]["text"]
                        : (int)$match["children"][0]["text"];
        }
    }
}

$result = [
    "frames" => [
    ]
];

if ($found_played_game) {
    $result["frames"][] = [
        "text" => html_entity_decode($match_state_previous . ' ' . str_replace(' ', '', $match_score_previous) . ' ' . $match_team2_previous . ' on ' . $match_date_previous, ENT_QUOTES | ENT_XML, 'UTF-8'),
        "icon" => "a2573"
    ];
}

if ($rank>0) {
    $result["frames"][] = [
        "goalData"=> [
            "start"   => 1,
            "current" => $rank,
            "end"     => 20,
            "unit"    => ""
        ],
        "icon" => "i20215"
    ];
}

if (!empty($match_date_next)) {
    $result["frames"][] = [
        "text" => html_entity_decode($match_date_next . " " . $match_team1_next . " " . $match_team2_next, ENT_QUOTES | ENT_XML1, 'UTF-8'),
        "icon" => "i14642"
    ];
}

echo json_encode($result);
