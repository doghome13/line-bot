<?php

namespace App\Console\Commands;

use App\Events\ThrowException;
use App\Exceptions\FailException;
use App\Notifications\TelegramBot;
use Artisan;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Notification;
use NotificationChannels\Telegram\TelegramChannel;

class UpdateByGit extends Command
{
    // 指令名稱
    protected $signature = 'update:test:daily {--withoutvue : 跳過 VUE 部分的更新}';

    // Command 說明
    protected $description = '更新專案';

    public function __construct()
    {
        parent::__construct();

        // 需改成讀取環境檔的參數
        $this->dir        = '/home/qa_lab/c01-agent-test';
        $this->vueDir     = '/home/qa_lab/c01-vue';
        $this->SSH        = '';
        $this->vueSSh     = '';
        $this->remote     = 'gd';
        $this->bracnh     = '_test';
        $this->logPath    = 'storage/logs/';
        $this->vueVersion = '';
        $this->gitHEAD    = '';
        $this->redisKey   = 'agent:update:locked';
    }

    public function handle()
    {
        try {
            if (redis('cache')->exists($this->redisKey)) {
                $this->line('還在更新中');
                $notification = (new TelegramBot())->setText('還在更新中');
                Notification::route(TelegramChannel::class, 'testing')->notify($notification);

                return;
            }

            // locked
            redis('cache')->set($this->redisKey, 1);
            redis('cache')->expire($this->redisKey, 300);

            $withoutvue = $this->option('withoutvue');
            $this->removeLogs();

            $this->createLogs('Computing...');
            exec('ls -al app/Console/Commands | grep gitignore', $res);

            if (empty($res)) {
                shell_exec('touch app/Console/Commands/.gitignore');
                $this->line('created app/Console/Commands/.gitignore');

                exec('echo ".gitignore" >> app/Console/Commands/.gitignore');
                exec('echo "UpdateTestDaily.php" >> app/Console/Commands/.gitignore');
            }

            $res = [];
            exec('ls -al app/Notifications | grep gitignore', $res);

            if (empty($res)) {
                shell_exec('touch app/Notifications/.gitignore');
                $this->line('created app/Notifications/.gitignore');

                exec('echo ".gitignore" >> app/Notifications/.gitignore');
                exec('echo "TelegramBot.php" >> app/Notifications/.gitignore');
            }

            if ($withoutvue) {
                $this->createLogs('--withoutvue=true');
            }

            // backup
            $this->info('Backup /web');
            shell_exec('cd '.$this->dir);
            shell_exec('rm -rf public/web_backup');
            shell_exec('cp -avr public/web public/web_backup');
            shell_exec('touch public/web_backup/.gitignore');
            exec('echo ".gitignore" >> public/web_backup/.gitignore');
            exec('echo "*" >> public/web_backup/.gitignore');

            $this->initGit($this->dir);
            $this->fire();
            $this->recordHead($this->dir);
            $this->line('==================');

            if ($withoutvue) {
                $this->info('Rollback /web');
                shell_exec('cd '.$this->dir);
                shell_exec('rm -rf public/web');
                shell_exec('cp -avr public/web_backup public/web');
                shell_exec('rm -rf public/web_backup');
                shell_exec('rm public/web/.gitignore');
            } else {
                $this->initGit($this->vueDir);
                $this->buildVue();
                shell_exec('cd '.$this->dir.';rm -rf public/web_backup');
                $this->line('==================');
            }

            shell_exec('cd '.$this->dir);
            $this->call('config:cache');
            $this->call('route:cache');

            // migration
            // $this->call('migrate');
            /*
                Artisan::call('migrate');
                $migrations = Artisan::output();

                if (str_contains($migrations, 'Nothing')) {
                    $migrations = str_replace(["\n", "\t", "\r"], '', $migrations);
                    $this->info($migrations);
                    $this->createLogs($migrations);
                } else {
                    $migrations = explode('\n', $migrations);

                    foreach ($migrations as $log) {
                        $this->info($log);
                        $this->createLogs($log);
                    }
                }
            */

            redis('cache')->del($this->redisKey);
            $this->info('Finished!');
            $this->createLogs('Finished!');
            $notification = (new TelegramBot())
                ->setAnimation('success')
                ->setText('更新完畢!')
                ->silent();
            Notification::route(TelegramChannel::class, 'testing')
                ->notify($notification);
        } catch (FailException $th) {
            exec('cd '.$this->dir.";find . --name 'web_backup'", $find);

            if (! empty($find)) {
                $this->info('Rollback /web');
                $path = 'cd '.$this->dir;
                shell_exec($path.';rm -rf public/web');
                shell_exec($path.';cp -avr public/web_backup public/web');
                shell_exec($path.';rm -rf public/web_backup');
                shell_exec($path.';rm public/web/.gitignore');
            }

            redis('cache')->del($this->redisKey);
            $this->error($th->getMessage());
            $this->error($th->getLine());
            $this->createLogs('Exception: ');
            $this->createLogs('MSG: '.$th->getMessage());
            $this->createLogs('Line: '.$th->getLine());

            $notification = (new TelegramBot())
                ->setAnimation('error')
                ->setText($th->getMessage());
            Notification::route(TelegramChannel::class, 'testing')
                ->notify($notification);

            // $th->setStatusCode(403);
            // event(new ThrowException($th));
        }
    }

    public function initGit($dir)
    {
        $this->info('Init Git');
        $ssh = '';

        switch ($dir) {
            case $this->dir:
                $ssh = $this->SSH;
                break;

            case $this->vueDir:
                $ssh = $this->vueSSh;
                break;

            default:
                throw new FailException('invalid dir and ssh', 9090);
                break;
        }

        exec('cd '.$dir.';git remote show', $res);

        if (! in_array($this->remote, $res)) {
            shell_exec('cd '.$dir.';git remote add '.$this->remote.' '.$ssh);
            $this->createLogs('git remote add '.$this->remote.' '.$ssh);
        }

        $commands = [
            'git fetch '.$this->remote,
            'git remote prune '.$this->remote,
            'git gc',
        ];

        foreach ($commands as $command) {
            shell_exec('cd '.$dir.';'.$command);
            $this->createLogs($command);
        }

        // 確認版本
        $this->vueVersion = shell_exec('cd '.$dir.';'.'git log '.$this->remote.'/'.$this->bracnh.' --oneline -1');
        $this->vueVersion = str_replace(["\n", "\t", "\r"], '', $this->vueVersion);
        $this->vueVersion = htmlentities($this->vueVersion);
        $this->createLogs('BRANCH '.$this->remote.'/'.$this->bracnh.': ');
        $this->createLogs($this->vueVersion);
        $this->info('BRANCH '.$this->remote.'/'.$this->bracnh.': '.$this->vueVersion);
        $this->info('Init Git Completed');
    }

    public function fire()
    {
        $this->info('Git Fetch '.$this->remote.', Branch '.$this->bracnh);

        $pendding = [
            '.vscode/settings.json',
            // 'composer.json',
            // 'config/app.php',
            // 'config/services.php',
            'composer.lock',
            // 'app/Console/Kernel.php',
            'storage/framework/laravel-excel/*',
        ];

        $path     = 'cd '.$this->dir;
        $commands = [
            'find . -name "*.ini" -exec rm -rf {} \;',
            'git config --local user.email "autoupdate@system.com"',
            'git config --local user.name "Auto Update"',
            'git checkout -b "branch_tmp"',
            'git add . --ignore-removal',
            'git reset '.implode(' ', $pendding),
            'git commit -m "tmp"',
            'git stash',
            'git checkout '.$this->remote.'/'.$this->bracnh,
            'git stash pop; git reset HEAD .',
            'git branch -D branch_tmp',
        ];

        foreach ($commands as $command) {
            shell_exec($path.';'.$command);
            $this->createLogs($command);
        }

        exec('git stash list', $check);

        if (! empty($check)) {
            shell_exec('git merge --abort');
            shell_exec('git stash clear');
        }
    }

    public function buildVue()
    {
        $this->info('Start Build Vue');
        $this->info('Git Fetch '.$this->remote.', Branch '.$this->bracnh);

        // 版本如果一樣就跳過 BUILD
        $this->recordHead($this->vueDir);
        $match = explode(' ', $this->gitHEAD);

        if (str_contains($this->vueVersion, $match[0])) {
            $path = 'cd '.$this->dir;
            shell_exec($path.';rm -rf public/web');
            shell_exec($path.';cp -avr public/web_backup public/web');
            shell_exec($path.';rm -rf public/web_backup');
            shell_exec($path.';rm public/web/.gitignore');
            $this->info('Same Branch Version');
            $this->createLogs('same branch version');
            // return;
        }

        $pendding = [
            'vue.config.js',
            '.vscode/settings.json',
        ];

        $path     = 'cd '.$this->vueDir;
        $commands = [
            $path.';find . -name "*.ini" -exec rm -rf {} \;',
            $path.';git config --local user.email "autoupdate@system.com"',
            $path.';git config --local user.name "Auto Update"',
            $path.';git add . --ignore-removal',
            $path.';git reset '.implode(' ', $pendding),
            $path.';git commit -m "rollback_tmp"',
            $path.';git stash',
            $path.';git checkout '.$this->remote.'/'.$this->bracnh,
            $path.';git stash pop',
        ];

        foreach ($commands as $command) {
            shell_exec($command);
            $this->createLogs($command);
        }

        // // 釋放暫存
        // $check = shell_exec($path . ';git log -1 --oneline');

        // if (str_contains($check, 'rollback_tmp')) {
        //     shell_exec($path . ';git reset HEAD^');
        //     $this->createLogs('git reset HEAD^');
        // }

        $output = [];
        exec('scl enable rh-nodejs10 "'.$path.';npm run build"', $output);
        $this->createLogs('npm run build');

        foreach ($output as $index => $log) {
            if (str_contains($log, 'ERROR in')) {
                $this->line($log);
                $this->createLogs($log);
                throw new FailException($output[$index + 1], 9090);
            } elseif (str_contains($log, 'WARNING in')) {
                $this->line($log);
                $this->error($output[$index + 1]);
                $this->createLogs($log);
                $this->createLogs($output[$index + 1]);
                throw new FailException($output[$index + 1], 9090);
            }
        }

        $output = [];
        exec($path.';ls -al dist/*', $output);

        if (empty($output)) {
            throw new FailException('empty dist', 9090);

            return;
        }

        // 替換檔案
        $commands = [
            'rm -rf '.$this->dir.'/public/web/*',
            'mv '.$this->vueDir.'/dist/* '.$this->dir.'/public/web/',
        ];

        foreach ($commands as $command) {
            shell_exec($command);
            $this->createLogs($command);
        }
    }

    public function createLogs($msg = null)
    {
        if (is_null($msg) || $msg === '') {
            return;
        }

        $now      = Carbon::now();
        $fileName = 'cron-'.$now->format('Y-m-d').'.log';

        exec('ls '.$this->logPath.'| grep '.$fileName, $res);

        if (empty($res)) {
            shell_exec('touch '.$this->logPath.$fileName);
        }

        shell_exec('echo ['.$now->format('H:i:s').'] "'.$msg.'" >> '.$this->logPath.$fileName);
    }

    public function removeLogs()
    {
        $now    = Carbon::now();
        $days   = 5;
        $remain = [];

        while ($days >= 0) {
            $fileName = 'cron-'.$now->copy()->subDays($days)->format('Y-m-d').'.log';
            array_push($remain, $fileName);
            $days--;
        }

        exec('ls '.$this->logPath.' | grep "cron-"', $logs);
        $logs = array_diff($logs, $remain);

        if (empty($logs)) {
            return;
        }

        shell_exec('cd '.$this->logPath.';rm '.implode(' ', $logs));
    }

    /**
     * 紀錄當前版本.
     */
    public function recordHead($dir)
    {
        exec('cd '.$dir.';'.'git status', $res);

        foreach ($res as $log) {
            if (str_contains($log, 'HEAD')) {
                $log = htmlentities($log);
                $this->createLogs($log);
                break;
            }
        }

        $res = shell_exec('cd '.$dir.';'.'git log --oneline -1');
        $res = str_replace(["\n", "\t", "\r"], '', $res);
        $res = htmlentities($res);
        $this->createLogs($res);
        $this->gitHEAD = $res;
    }
}
