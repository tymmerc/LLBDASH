<?php

include "connect.php";

function getNbPost()
{
    $connexion = connect();
    $req = "SELECT count(*) as nbr_article_total FROM wp_wl2ccf_posts WHERE post_type='post' AND post_status='publish' ";
    $res = $connexion->query($req);
    $data = $res->fetch();
    $nbPost = $data["nbr_article_total"];
    return $nbPost;
}

//retourne le nombre d'auteurs 
function getNbAuthor()
{
    $connexion = connect();
    $req = "SELECT count(*) as nbAuthor FROM wp_wl2ccf_users ";
    $res = $connexion->query($req)->fetch();
    $nbAuthor = $res["nbAuthor"];
    return $nbAuthor;
}

//retourne la date de la dernière publication
function getLastPost()
{
    $connexion = connect();
    $req = "SELECT MAX(post_date) AS post_date
    FROM wp_wl2ccf_posts
    WHERE post_status = 'publish';";
    $res = $connexion->query($req)->fetch();
    if ($res && !empty($res["post_date"])) {
        $date = new DateTime($res["post_date"]);
        return $date->format('d/m/Y');
    }
    return "Aucune publication trouvée";
}



//retourne le nombre d'auteurs actifs
function getActiveAuthors()
{
    $connexion = connect();
    $req = "SELECT COUNT(DISTINCT post_author) as active_authors 
            FROM wp_wl2ccf_posts 
            WHERE post_status='publish' AND post_type='post'";
    $res = $connexion->query($req);
    $data = $res->fetch();
    $activeAuthors = $data["active_authors"];
    return $activeAuthors;
}

//retourne les catégories les plus populaires
function getPopularCategories()
{
    $connexion = connect();
    $req = "SELECT wp_wl2ccf_terms.name, COUNT(*) as PopularCategories
    FROM wp_wl2ccf_posts
    JOIN wp_wl2ccf_term_relationships
    ON wp_wl2ccf_posts.ID = wp_wl2ccf_term_relationships.object_id
    JOIN wp_wl2ccf_term_taxonomy
    ON wp_wl2ccf_term_relationships.term_taxonomy_id = wp_wl2ccf_term_taxonomy.term_taxonomy_id
    JOIN wp_wl2ccf_terms
    ON wp_wl2ccf_term_taxonomy.term_id = wp_wl2ccf_terms.term_id
    WHERE taxonomy = 'category' AND post_type = 'post' AND post_status = 'publish'
    GROUP BY wp_wl2ccf_terms.term_id
    HAVING COUNT(*) > 7
    ORDER BY wp_wl2ccf_terms.name";
    $res = $connexion->query($req);
    $categories = [];

    //recupere nb total articles
    $totalPostsReq = "SELECT COUNT(*) as total_posts FROM wp_wl2ccf_posts WHERE post_type='post' AND post_status='publish'";
    $totalPostsRes = $connexion->query($totalPostsReq);
    $totalPostsData = $totalPostsRes->fetch();
    $totalPosts = $totalPostsData['total_posts'];

    //parcours resultats
    while ($data = $res->fetch()) {
        $categories[] = [
            'name' => $data['name'],
            'PopularCategories' => $data['PopularCategories'],
            'percentage' => ($totalPosts > 0) ? round(($data['PopularCategories'] / $totalPosts) * 100, 2) : 0
        ];
    }

    return $categories;
}



function getLast6Posts()
{
    $connexion = connect();
    $req = "SELECT post_title, post_date, guid 
            FROM wp_wl2ccf_posts 
            WHERE post_status = 'publish' 
              AND post_type = 'post' 
            ORDER BY post_date DESC 
            LIMIT 6";
    $res = $connexion->query($req);

    //recupere tout les articles avec le guid
    $posts = $res->fetchAll(PDO::FETCH_ASSOC);

    return $posts;
}


function getNbComments()
{
    $connexion = connect();
    $req = "SELECT COUNT(*) as total_comments 
            FROM wp_wl2ccf_comments 
            WHERE comment_approved = 1";
    $res = $connexion->query($req);
    $data = $res->fetch();
    return $data['total_comments'];
}

function getNbSpamComments()
{
    $connexion = connect();
    $req = "SELECT COUNT(*) as spam_comments 
            FROM wp_wl2ccf_comments 
            WHERE comment_approved = 'spam'";
    $res = $connexion->query($req);
    $data = $res->fetch();
    return $data['spam_comments'];
}
?>