<?php


namespace App\Util;


use function dirname;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class EnvUtil
{
    /**
     *.env file overwrite
     */
    public static function overWriteEnvFile($type, $val): void
    {
        $path = str_replace('src','', dirname(__DIR__)).'.env';
        if (file_exists($path)) {
            //$val = (is_string($val))?'"'.trim($val).'"':trim($val);
            if(is_numeric(strpos(file_get_contents($path), $type))
                && strpos(file_get_contents($path), $type) >= 0){

                if (str_contains($val,'#')){
                    file_put_contents($path, str_replace(
                        $type.'="'.$_ENV[$type].'"', $type.'='.'"'.trim($val).'"', file_get_contents($path)
                    ));
                }else{
                    file_put_contents($path, str_replace(
                        $type.'='.$_ENV[$type], $type.'='.trim($val), file_get_contents($path)
                    ));
                }


            }
            else{
                $val = '"'.trim($val).'"';
                file_put_contents($path, file_get_contents($path)."\r\n".$type.'='.$val);
            }
        }
    }

    /**
     * @param KernelInterface $kernel
     * @param $command
     * @return Response
     * @throws Exception
     */
    public static function do_command(KernelInterface $kernel, $command)
    {
        $env = $kernel->getEnvironment();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => $command,
            '--env' => $env
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);

        $content = $output->fetch();

        return new Response($content);
    }
}
