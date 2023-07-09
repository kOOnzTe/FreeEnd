<?php


class Upload{
    private $filename = null;
    private $error = null;


    public function __construct($file, $uploadFolder)
    {
     
        if(!empty($_FILES[$file]["name"])){
            $filename = $_FILES[$file]["name"];
            $tmp_name = $_FILES[$file]["tmp_name"];
            $size = $_FILES[$file]["size"];

            $ext = pathinfo($filename, PATHINFO_EXTENSION); 
            $whitelist = [
                "gif",
                "png", 
                "jpeg",
                "jpg",
                "bmp"
            ];
            if( !in_array($ext, $whitelist)){
                $this->error ="wrong file type";
            }else if( $size > 1024 * 1024) 
            {
                $this->error = "too big";
            }else {
                $this->filename = sha1("Project 256" . uniqid() . $ext); 
  
                if( !move_uploaded_file($tmp_name, $uploadFolder . "/" . $this->filename)){
                    $this->error = "system error";
                    $this->filename = null;
                }


            }

        }else{
            $this->error = "no file uploaded";
        }
    }

    public function file(){
        return $this->filename;
    }

    public function error(){
        return $this->error;
    }

}