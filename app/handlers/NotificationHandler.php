class NotificationHandler {
    
    public function fire($job, $data) {
        
        // get first 100 notifications in the table (since every entry fires a job this must be enough)
        $notifications = DB::table('notification_queue')->take(100)->orderBy('user_id', 'asc')->get();

        $previousId = 0;

        foreach ($notifications as $notif) {
            if($notif->user_id !== previousId) {
                $previousId = $notif->user_id;

                // grab the token and os info from DB
            }
        }


        // in the end, delete job from queue
        $job->delete();
    }
}