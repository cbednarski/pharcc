<?php

namespace cbednarski\pharcc;

class Git
{
    /**
     * Determine the git version based on `git describe` and semver conventions.
     *
     * @see http://semver.org/
     * @see https://www.kernel.org/pub/software/scm/git/docs/git-describe.html
     *
     * Normally we'll pull a version number like:
     *   1.0.4      Tagged commit
     *   1.0.4+306  Untagged commit on top of a tag
     *
     * If the strict param is true, we will show 'unknown' in the untagged case
     *
     * @param  string $path   Directory to inspect for git tags
     * @param  bool   $strict Whether or not we'll allow untagged commits
     * @return string Version number or 'unknown' in a failure case
     */
    public static function getVersion($path, $strict = false)
    {
        $temp_path = getcwd();
        // Git needs the cwd to be inside the repo
        // proc_open will pick this up automatically
        chdir($path);

        $descriptorspec = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w'), // stderr
        );

        $process = proc_open('git describe', $descriptorspec, $pipes);
        $return_value = null;

        if (is_resource($process)) {
            $raw_version = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            $return_value = proc_close($process);
        }

        if ($return_value !== 0) {
            return 'unknown';
        }

        if (1 === preg_match('/^(\d+\.\d+.\d+)(?:\-(\d+)\-[\w\d]+)?$/', $raw_version, $matches)) {
            if (isset($matches[2])) {
                if ($strict) {
                    return 'unknown';
                } else {
                    $version = $matches[1] . '+' . $matches[2];
                }
            } else {
                $version = $matches[1];
            }
        } else {
            return 'unknown';
        }

        // Reset the working directory
        chdir($temp_path);

        return $version;
    }

}
