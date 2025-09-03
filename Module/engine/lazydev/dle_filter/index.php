<?php

include_once __DIR__ . "/loader.php";

$microTimer = new microTimer();

// --- Initialize filter ---
$Filter = LazyDev\Filter\Filter::construct()
    ->load($vars)
    ->getVar()
    ->getPage()
    ->filterOptions()
    ->order()
    ->setUrl();

$url_page = $Filter::$urlFilter;

// --- AJAX form exit ---
if ($vars["ajax"] && $configVar["ajax_form"]) {
    return null;
}

// --- Build filter ID (URL) ---
$filterID = $Filter::$urlFilter . "/";
if ($Filter::$pageFilter > 1) {
    $filterID .= "page/" . $Filter::$pageFilter . "/";
}

// --- Redirect if needed ---
if (!$vars["ajax"]) {
    $homeUrl = LazyDev\Filter\Helper::cleanSlash($config["http_home_url"]);
    $_SERVER["REQUEST_URI"] = $homeUrl . rawurldecode($_SERVER["REQUEST_URI"]);
    $filterID = str_replace([" ", "%20"], "+", rawurldecode($filterID));

    if (
        $filterID != $_SERVER["REQUEST_URI"] ||
        substr($_SERVER["REQUEST_URI"], -1) != "/" ||
        substr_count($_SERVER["REQUEST_URI"], "/page/1/")
    ) {
        if (!headers_sent()) {
            header("HTTP/1.0 301 Moved Permanently");
            header("Location: " . $filterID);
        }
        exit("Redirect");
    }
}

// --- Conditions instance ---
$Conditions = LazyDev\Filter\Conditions::construct();

// --- Cache check ---
if ($configVar["cache_filter"]) {
    $cacheFilter = LazyDev\Filter\Cache::get($filterID);
    if ($cacheFilter) {
        $cacheFilter = json_decode($cacheFilter, true);

        if ($configVar["redirect_filter"] && !$vars["ajax"]) {
            $Filter::redirect($cacheFilter["countNews"]);
        }

        $tpl->result["content"] = $cacheFilter["content"];
        $tpl->result["navigation"] = $cacheFilter["navigation"];

        if ((float)$config["version_id"] >= 14) {
            $tpl->result["content"] = LazyDev\Filter\Filter::navigation();
        }

        $metatags["title"]       = $cacheFilter["seo"]["title"] ?: $metatags["title"];
        $metatags["description"] = $cacheFilter["seo"]["description"] ?: $metatags["description"];
        $metatags["keywords"]    = $cacheFilter["seo"]["keywords"] ?: $metatags["keywords"];
        $count_all               = $cacheFilter["countNews"];
        $tpl->result["speedbar"] = $cacheFilter["seo"]["speedbar"];

        $metatags["keywords"] .= $Filter::metaRobots();

        if ($configVar["statistics"] && $Filter::$pageFilter < 2) {
            $Filter::setStatistics([
                "mysqlTime"   => -1,
                "templateTime"=> -1,
                "foundNews"   => $cacheFilter["foundNews"],
                "queryNumber" => -1,
                "statistics"  => $cacheFilter["id"],
                "sqlQuery"    => $db->safesql($cacheFilter["sqlQuery"])
            ]);
        }

        $config["speedbar"] = false;
        return null;
    }
}

// --- News restrictions ---
if ($config["no_date"] && !$config["news_future"]) {
    $Filter::$sqlWhere[] = "date < '" . date("Y-m-d H:i:s") . "'";
}

// User group restrictions
if (!$user_group[$member_id["user_group"]]["allow_short"]) {
    $notAllowedCat = explode(",", $user_group[$member_id["user_group"]]["not_allow_cats"]);
    if ($notAllowedCat[0]) {
        if ($config["allow_multi_category"]) {
            if ($config["version_id"] > 13.1) {
                $Filter::$sqlWhere[] =
                    "p.id NOT IN (
                        SELECT DISTINCT(" . PREFIX . "_post_extras_cats.news_id)
                        FROM " . PREFIX . "_post_extras_cats
                        WHERE cat_id IN (" . implode(",", $notAllowedCat) . ")
                    )";
            } else {
                if ($Filter::$oldMySQL) {
                    $Filter::$sqlWhere[] =
                        "category NOT REGEXP '[[:<:]](" . implode("|", $notAllowedCat) . ")[[:>:]]'";
                } else {
                    $Filter::$sqlWhere[] =
                        "category REGEXP '([[:punct:]]|^)(" . implode("|", $notAllowedCat) . ")([[:punct:]]|\$)'";
                }
            }
        } else {
            $Filter::$sqlWhere[] = "category NOT IN ('" . implode("','", $notAllowedCat) . "')";
        }
    }
}

// Only main page news
if ($configVar["allow_main"]) {
    $Filter::$sqlWhere[] = "allow_main=1";
}

// --- SQL preparation ---
$filterSqlWhere = $Filter::$sqlWhere ? " AND " . implode(" AND ", $Filter::$sqlWhere) : "";
$allow_active_news = true;

// Temporary category ID for template
$category_id = 999999;
$cat_info[$category_id]["short_tpl"] = "lazydev/dle_filter/news";

$sql_count = $Filter::sqlCount() . $filterSqlWhere;

$config["news_number"] = $configVar["news_number"] ?: $config["news_number"];
$cstart = $Filter::$pageFilter ? ($Filter::$pageFilter - 1) * $config["news_number"] : 0;

$sql_select = $Filter::sqlSelect()
    . $filterSqlWhere
    . " ORDER BY " . $Filter::$orderBy
    . " LIMIT " . $cstart . "," . $config["news_number"];

$tpl->is_custom = true;

// --- Load news ---
include DLEPlugins::Check(ENGINE_DIR . "/modules/show.short.php");

// Redirect after filter
if ($configVar["redirect_filter"] && !$vars["ajax"]) {
    $Filter::redirect($count_all);
}

// --- No results handling ---
if (!$news_found) {
    if ($configVar["code_filter"] == 404) {
        @header("HTTP/1.0 404 Not Found");
    }
    $tpl->load_template("info.tpl");
    $tpl->set("{error}", $langVar["site"]["not_found"]);
    $tpl->set("{title}", $langVar["site"]["info"]);
    $tpl->compile("content");
    $tpl->clear();
} else {
    if ($config["files_allow"] && strpos($tpl->result["content"], "[attachment=") !== false) {
        $tpl->result["content"] = show_attach($tpl->result["content"], $attachments);
    }
}

// Navigation
if ((float)$config["version_id"] >= 14) {
    $tpl->result["content"] = LazyDev\Filter\Filter::navigation();
}

// Cleanup temporary category
unset($cat_info[$category_id]);
$category_id = false;

// --- SEO processing ---
$Filter::$seoView->result["seo"] = $Conditions::realize(
    $Filter::$seoView->result["seo"],
    $Filter::$filterData
);

// Meta tags extraction
if (preg_match("'\\[meta-title\\](.*?)\\[/meta-title\\]'si", $Filter::$seoView->result["seo"], $m) && $m[1]) {
    $metatags["title"] = $m[1];
}
if (preg_match("'\\[meta-description\\](.*?)\\[/meta-description\\]'si", $Filter::$seoView->result["seo"], $m) && $m[1]) {
    $metatags["description"] = $m[1];
}
if (preg_match("'\\[meta-keywords\\](.*?)\\[/meta-keywords\\]'si", $Filter::$seoView->result["seo"], $m) && $m[1]) {
    $metatags["keywords"] = $m[1];
}
$metatags["keywords"] .= $Filter::metaRobots();
if (preg_match("'\\[meta-speedbar\\](.*?)\\[/meta-speedbar\\]'si", $Filter::$seoView->result["seo"], $m) && $m[1]) {
    $metatags["speedbar"] = $m[1];
}

$metatags = $Conditions::cleanArray($metatags);

// Speedbar rendering
if ($config["speedbar"] && $metatags["speedbar"]) {
    $tpl->load_template("lazydev/dle_filter/speedbar.tpl");
    $tpl->set("{site-name}", $config["short_title"]);
    $tpl->set("{site-url}", $config["http_home_url"]);
    $tpl->set("{separator}", $config["speedbar_separator"]);
    $tpl->set("{filter-name}", $metatags["speedbar"]);
    $tpl->set("{filter-url}", $url_page . "/");
    $tpl->set("{page-descr}", $lang["news_site"]);
    $tpl->set("{page}", $Filter::$pageFilter);

    if ($Filter::$pageFilter > 1) {
        $tpl->set_block("'\\[second\\](.*?)\\[/second\\]'si", "\\1");
        $tpl->set_block("'\\[first\\](.*?)\\[/first\\]'si", "");
    } else {
        $tpl->set_block("'\\[second\\](.*?)\\[/second\\]'si", "");
        $tpl->set_block("'\\[first\\](.*?)\\[/first\\]'si", "\\1");
    }

    $tpl->compile("speedbar");
    $tpl->clear();
    $config["speedbar"] = false;
}

// --- Cache store ---
if ($configVar["cache_filter"]) {
    $cacheArray = [
        "content"    => $tpl->result["content"],
        "seo"        => [
            "title"       => $metatags["title"],
            "description" => $metatags["description"],
            "keywords"    => $metatags["keywords"],
            "speedbar"    => $tpl->result["speedbar"]
        ],
        "navigation" => $tpl->result["navigation"],
        "url"        => $Filter::$urlFilter,
        "id"         => $filterID,
        "sqlQuery"   => $sql_select,
        "foundNews"  => intval($news_found),
        "countNews"  => $count_all
    ];
    LazyDev\Filter\Cache::set(LazyDev\Filter\Helper::json($cacheArray), $filterID);
}

// --- Statistics ---
if ($configVar["statistics"] && $Filter::$pageFilter < 2) {
    $Filter::setStatistics([
        "mysqlTime"   => $db->MySQL_time_taken,
        "templateTime"=> $tpl->template_parse_time,
        "foundNews"   => intval($news_found),
        "queryNumber" => $db->query_num,
        "statistics"  => $filterID,
        "sqlQuery"    => $db->safesql($sql_select)
    ]);
}

?>
