<?php

namespace NBDImposer;

class Preset extends Singleton
{
    public function getAll()
    {
        $posts = get_posts([
            'post_type' => PresetPostType::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        return $posts;
    }
}