{ pkgs }:

let
  php = pkgs.php84.buildEnv {
    extensions = ({ enabled, all }: enabled ++ (with all; [
      bcmath
      curl
      fileinfo
      mbstring
      openssl
      pdo
      pdo_mysql
      tokenizer
      xml
      zip
    ]));
  };
in
{
  deps = [
    php
    pkgs.php84Packages.composer
    pkgs.nodejs_24
  ];
}
