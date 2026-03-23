# BiscaGame

## Visão Geral

O **BiscaGame** é uma plataforma web para jogar **Bisca** em modo **single-player** e **multiplayer**, com suporte a partidas rápidas e matches completos. O sistema foi pensado como uma aplicação distribuída, onde a interface, a lógica de negócio e a comunicação em tempo real estão separadas em serviços independentes.

Mais do que apenas um jogo, o projeto reúne numa única solução:

- jogabilidade local contra bot para treino;
- jogabilidade online entre jogadores autenticados;
- sistema de moedas e transações;
- histórico de jogos e matches;
- estatísticas pessoais e públicas;
- leaderboard global;
- gestão administrativa da plataforma;
- monitorização do estado dos serviços.

## O Que o Projeto Faz

A aplicação permite que diferentes tipos de utilizador interajam com a plataforma consoante o seu perfil:

- **Visitantes** podem explorar a aplicação e jogar em modo offline contra o bot.
- **Jogadores autenticados** podem criar ou entrar em jogos online, participar em matches competitivos, consultar o seu perfil, ver histórico, estatísticas e movimentos de moedas.
- **Administradores** têm acesso a um painel de controlo com informação operacional da plataforma, gestão de utilizadores, admins, transações e partidas globais.

O projeto suporta duas variantes do jogo:

- **Bisca de 3**
- **Bisca de 9**

No modo online, a plataforma trata da criação de lobbies, entrada dos jogadores, sincronização do estado do jogo em tempo real, validação de jogadas, controlo de prontidão, desistências, reconexões e fecho das partidas.

## Constituintes do Projeto

O sistema está organizado em três componentes principais e um conjunto de serviços de suporte.

### 1. Frontend

O `frontend` é a camada de apresentação da plataforma. Foi desenvolvido em **Vue 3** com **Vite**, e é responsável por toda a experiência do utilizador.

Inclui, entre outras, as seguintes áreas:

- página principal e navegação global;
- autenticação e recuperação de conta;
- seleção de modos de jogo;
- jogo offline contra bot;
- jogo multiplayer em tempo real;
- lobby de jogos e matches disponíveis;
- perfil do jogador;
- histórico detalhado de partidas;
- estatísticas públicas e pessoais;
- leaderboard;
- área administrativa.

Além da interface visual, o frontend também gere o estado da sessão do utilizador, a comunicação com a API e a integração com o canal de tempo real usado durante os jogos online.

### 2. API

A `api` é o núcleo da lógica de negócio e foi desenvolvida em **Laravel**. É a responsável por expor os endpoints que suportam toda a plataforma.

As suas responsabilidades incluem:

- registo, login, verificação de email e recuperação de password;
- gestão de perfil do jogador;
- atualização de palavra-passe e eliminação de conta;
- criação e gestão de jogos e matches;
- histórico de partidas;
- estatísticas públicas e pessoais;
- leaderboard;
- gestão de moedas, compras e transações;
- funcionalidades administrativas;
- persistência de utilizadores, jogos, matches e transações.

No backend existe também a lógica central das regras da Bisca, incluindo:

- criação e embaralhamento do baralho;
- distribuição de cartas;
- cálculo de jogadas e vazas;
- contagem de pontos;
- determinação de marks em matches;
- gestão de fim de jogo, empate, desistência e interrupção.

### 3. WebSocket Server

O serviço `websocket` é responsável pela comunicação **em tempo real** entre os jogadores durante as partidas online.

Este componente:

- gere as ligações WebSocket dos clientes;
- autentica utilizadores para sessões multiplayer;
- organiza salas de jogo;
- recebe e propaga eventos como entrar no jogo, jogar carta, ready/unready e desistência;
- ajuda a manter os dois jogadores sincronizados;
- comunica com Redis e com a API para refletir alterações de estado.

Sem este serviço, a experiência multiplayer deixaria de ser interativa e imediata.

## Serviços de Suporte

Para além dos três componentes principais, o projeto depende de serviços auxiliares que suportam a operação da plataforma:

- **PostgreSQL** para persistência de dados;
- **Redis** para estado temporário, coordenação e mensagens entre serviços;
- **Queue Worker** para processamento assíncrono no backend;
- **Mailpit** para simulação e inspeção de emails em ambiente de desenvolvimento;
- **Docker** para uniformizar a execução dos vários serviços.

## Funcionalidades Principais

### Jogo Offline

O utilizador pode jogar localmente contra um bot, sem necessidade de moedas e sem impacto no histórico competitivo. Este modo serve sobretudo para treino e experimentação das regras do jogo.

### Jogo Multiplayer

Os jogadores autenticados podem:

- criar jogos rápidos;
- criar matches com stake em moedas;
- entrar em lobbies existentes;
- disputar partidas em tempo real;
- acompanhar a evolução do estado da partida de forma sincronizada.

### Sistema de Moedas

O projeto inclui uma economia interna baseada em moedas, usada para suportar modos competitivos. Este sistema permite:

- saldo por utilizador;
- custos de entrada em jogos;
- stakes em matches;
- registo de créditos e débitos;
- histórico de transações;
- associação entre partidas e movimentos de moedas.

### Estatísticas e Histórico

A plataforma guarda informação sobre jogos e matches multiplayer, permitindo ao jogador consultar:

- histórico de partidas;
- detalhe de matches e jogos;
- desempenho individual;
- estatísticas agregadas;
- classificação global.

### Administração

Existe uma área administrativa dedicada à gestão da plataforma, incluindo:

- visualização do estado dos serviços;
- gestão de utilizadores;
- bloqueio e desbloqueio de contas;
- gestão de administradores;
- consulta de transações;
- consulta de jogos e matches globais.

## Estrutura Geral

Ao nível mais alto, o projeto distribui-se da seguinte forma:

- `frontend/` contém a aplicação cliente;
- `api/` contém a API, a lógica de negócio e a persistência;
- `websocket/` contém o servidor de comunicação em tempo real;
- `setUp.md` contém as instruções de arranque e operação;
- `start-services.sh` centraliza o arranque conjunto dos serviços.

## Resumo

O **BiscaGame** é uma plataforma completa para jogar Bisca online, combinando interface web moderna, backend com regras de jogo e gestão de dados, e comunicação em tempo real para suportar partidas multiplayer. O projeto não se limita ao jogo em si: inclui autenticação, perfis, economia interna, estatísticas, histórico e ferramentas de administração, formando uma solução integrada e coerente.
