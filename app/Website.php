<?php

namespace App;

use App\Events\WebsiteEvent;
use App\Events\WebsiteUpdated;
use App\Jobs\ChangeApacheConfig;
use App\Jobs\ChangeWebsiteBranch;
use App\Jobs\CreateNewRepository;
use App\Jobs\RestartApache;
use App\Jobs\ChangeWebsiteConfig;
use App\Jobs\SwitchWebsiteState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Website extends Model
{
    protected $fillable = ['name', 'type', 'is_on', 'checkout', 'deploy_scripts', 'apache_config'];

    protected $appends = ['apache_config', 'apache_error_log_path'];

    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub

        parent::creating(function(Website $website) {
            # Create Web Username Owner from Name
            $website->username = str_slug($website->name);
            $website->password = substr(str_replace("$", "", Hash::make(str_random(8))), 0, 8);
            if (env('APP_ENV') != 'local') {
                # Check for Unique
                while (true) {
                    $result = shell_exec("grep -c '^{$website->username}:' /etc/passwd");
                    if ($result == "0\n") {
                        # Stop While
                        break;
                    } else {
                        $website->username .= "-" . substr(Hash::make(str_random(8)), 0, 3);
                    }
                }
            }
            $website->deploy_scripts = "# Those scripts below would be executed after a commit is pushed to repository\n";
            $website->activity_logs = "Waiting for initialization...\n";
        });

        parent::created(function(Website $website) {
            # Process Create Init Directory
            dispatch(new CreateNewRepository($website));
        });

        parent::updated(function(Website $website) {
            if ($website->isDirty(['is_on'])) {
                dispatch(new SwitchWebsiteState($website));
            }
            if ($website->isDirty('checkout')) {
                dispatch(new ChangeWebsiteBranch($website));
            }
            if ($website->isDirty('deploy_scripts')) {
                $deploy_path = "{$website->git_root}/hooks/post-receive";
                $scripts = view('scripts.post-receive', compact('website'))->render();
                dispatch(new ChangeWebsiteConfig($website, $deploy_path, $scripts, WebsiteEvent::DEPLOY_CHANGE));
            }
        });
    }

    # Mutators
    public function getApacheConfigAttribute() {
        if ($this->apache_path) {
            return file_get_contents($this->apache_path);
        }
        return "";
    }
    public function setApacheConfigAttribute($value) {
        if ($this->apache_config != $value) {
            dispatch(new ChangeWebsiteConfig($this, $this->apache_path, $value, WebsiteEvent::APACHE_CHANGE));
        }
    }
    public function getApacheErrorLogPathAttribute() {
        $config = $this->apache_config;
        preg_match_all("/(?<=ErrorLog )(.*)(?=\n)/", $config, $matches);
        if (isset($matches[1])) {
            return trim(array_first($matches[1]));
        }
        return null;
    }
    public function getApacheCustomLogPathAttribute() {
        $config = $this->apache_config;
        preg_match_all("/(?<=CustomLog )(.*)(?= combined|)/", $config, $matches);
        if (isset($matches[1])) {
            return trim(array_first($matches[1]));
        }
        return null;
    }
    public function getApacheFileAttribute() {
        return "{$this->id}-{$this->username}.conf";
    }
    # Helpers

}
