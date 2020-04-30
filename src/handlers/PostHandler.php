<?php
namespace src\handlers;

use \src\models\Post;
use \src\models\User;
use \src\models\UserRelation;

class PostHandler {
    public static function addPost($idUser, $type, $body) {
        $body = trim($body);

        if(!empty($idUser) && !empty($body)) {
        
            Post::insert([
                'id_user' => $idUser,
                'type' => $type,
                'created_at' => date('Y-m-d H:i:s'),
                'body' => $body
            ])->execute();
        
        }        
    }

    public static function getHomeFeed($idUser, $page) {
        $perPage = 2;
        // 1. pegar lista de usuarios que o usuario segue
        $userList = UserRelation::select()->where('user_from', $idUser)->get();
        $users =[];
        foreach($userList as $userItem) {
            $users[] = $userItem['user_to']; //aqui pega quem o usuario segue
        }
        $users[] = $idUser; //adiciona o proprio usuario na lista

        // 2. pegar os posts dessa galera ordenado pela data
        $postList = Post::select()
            ->where('id_user', 'in', $users)
            ->orderBy('created_at', 'desc')
            ->page($page, $perPage) // exibe dois posts por pagina
        ->get(); //pgear quando o id_user esta na lista por ordem de postagem
        
        $total = Post::select() // conta o numero de posts para paginação
            ->where('id_user', 'in', $users)
        ->count();
        $pageCount = ceil($total / $perPage); // total de paginas (ceil arredonda para cima)

        // 3. transormar o resultado em objetos dos models
        $posts = [];
        foreach($postList as $postItem) {
            $newPost = new Post();
            $newPost->id = $postItem['id'];
            $newPost->type = $postItem['type'];
            $newPost->created_at = $postItem['created_at'];
            $newPost->body = $postItem['body'];
            $newPost->mine = false;
            if($postItem['id_user'] == $idUser) { //se o post é do proprio usuario
                $newPost->mine = true;
            }
            // 4. preeencher as informações adicionais no post - do proprio usuario
            $newUser = User::select()->where('id', $postItem['id_user'])->one();
            $newPost->user = new User();
            $newPost->user->id = $newUser['id'];
            $newPost->user->name = $newUser['name'];
            $newPost->user->avatar = $newUser['avatar'];

            //TODO 4.1 Preencher informações de Like
            $newPost->likeCount = 0;
            $newPost->liked = false;
            //TODO 4.2 Preecher informações de COMMENTS
            $newPost->comments = [];

            $posts[] = $newPost; //coloca tudo no array criado

    
        }
        
        // 5. retornar o resultado

        //return $posts;
        return [
            'posts' => $posts,
            'pageCount' => $pageCount,
            'currentPage' => $page  //pagina atual
        ];
    }
}