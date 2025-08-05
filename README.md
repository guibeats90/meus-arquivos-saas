# DesireChat - Chat IA com Roleplay

Um site moderno e elegante para chat com avatares de IA, desenvolvido para o mercado português.

## Características

- Interface moderna e responsiva
- Galeria de avatares com personalidades únicas
- Sistema de chat em tempo real
- Assinatura mensal com mensagens grátis
- Painel administrativo completo
- Integração com APIs de IA (OpenRouter)

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Composer (para dependências)

## Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/desirechat.git
cd desirechat
```

2. Configure o banco de dados:
```bash
mysql -u seu_usuario -p < database.sql
```

3. Configure as credenciais do banco de dados:
Edite o arquivo `config/database.php` com suas credenciais:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'desirechat');
```

4. Configure o servidor web:
- Para Apache, certifique-se de que o mod_rewrite está ativado
- Para Nginx, adicione as regras de rewrite apropriadas

5. Configure as permissões:
```bash
chmod -R 755 .
chmod -R 777 uploads/ # Se houver upload de imagens
```

## Estrutura do Projeto

```
desirechat/
├── api/                 # APIs para chat e assinaturas
├── config/             # Configurações do banco de dados
├── admin/              # Painel administrativo
├── uploads/            # Imagens dos avatares
├── index.php           # Página inicial
├── login.php           # Página de login
├── register.php        # Página de registro
├── chat.php            # Interface de chat
├── subscription.php    # Página de assinatura
└── database.sql        # Estrutura do banco de dados
```

## Configuração da API de IA

Para integrar com a API de IA (OpenRouter), você precisará:

1. Criar uma conta em [OpenRouter](https://openrouter.ai/)
2. Obter sua chave de API
3. Configurar o endpoint no arquivo `api/send_message.php`

## Segurança

- Todas as senhas são hasheadas usando password_hash()
- Proteção contra SQL Injection usando prepared statements
- Validação de entrada em todos os formulários
- Sessões seguras com regeneração de ID
- Proteção contra CSRF (a ser implementado)

## Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## Suporte

Para suporte, envie um email para suporte@desirechat.com ou abra uma issue no GitHub. 