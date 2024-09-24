
<?php
session_start();

// Conectar ao banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bdmotion";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Checar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verifica se o usuário está logado
if (isset($_SESSION['user_id'])) {
    // Recuperar o tipo de usuário do banco de dados
    $user_id = intval($_SESSION['user_id']);
    $sql = "SELECT tipoUsuario FROM tbUsuario WHERE idUsuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['tipoUsuario'] = $row['tipoUsuario'];

        // Verifica se o tipo de usuário é gerente ou gerente regional
        if ($_SESSION['tipoUsuario'] === 'adm' || $_SESSION['tipoUsuario'] === 'gerenteRegional') {
            $_SESSION['access_denied'] = false;
        }
        } else {
        $_SESSION['access_denied'] = true;
    }
} else {
    $_SESSION['access_denied'] = true;
}  


// Obter o ID do candidato da URL
$idCandidato = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Verificar se o ID do candidato foi fornecido
if ($idCandidato > 0) {
    // Consulta SQL para recuperar os detalhes do candidato
    $sql = "SELECT c.idCandidato, c.nomeCandidato, c.emailCandidato, c.telefoneCandidato, 
            c.triagemCandidato, u.idUnidade, u.nomeUnidade 
            FROM tbCandidato c 
            JOIN tbUnidade u ON c.idUnidade = u.idUnidade
            WHERE c.idCandidato = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idCandidato);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $candidato = $result->fetch_assoc();
    } else {
        $_SESSION['mensagem'] = "Candidato não encontrado.";
    }
} else {
    $_SESSION['mensagem'] = "";
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RH - Candidatos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="../../css/projetoStyle.css">
    <link rel="stylesheet" href="../../css/consultarChamadoStyle.css">
    <style>
        .center-message {
            position: absolute;
            display: flex;
            justify-content: center;
            align-items: center;
            top :50%;
            right:42%
                
        }

        /* Estilo para a paginação fixa */
        .pagination-container {
            position: fixed;
            width: 100%;
            background-color: transparent;
            padding: 10px 0;
        }

        .pagination-container .pagination {
            justify-content: center;
        }

        .table {
            width: 100%;
            background: transparent;
            border-radius: 10px;
            overflow: hidden;
            color: #fff;
            border: 1px solid #dee2e6;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: .75rem;
            vertical-align: top;
            border: 1px solid #dee2e6;
        }

        thead {
            background-color: #93cc4c;
        }

        tbody {
            background-color: transparent;
        }
    </style>
</head>

<body>

    <div class="container">

        <?php include '../../components/navBar.php'; ?>

        <div class="row p-3">

            <?php include '../../components/sideBar.php'; ?>

            <div class="col-md-11">
                <div class="row p-2">
                    <?php
                    // Habilitar exibição de erros para diagnóstico
                    error_reporting(E_ALL);
                    ini_set('display_errors', 1);

                    // Conectar ao banco de dados
                    $servername = "localhost";
                    $username = "root";
                    $password = "";
                    $dbname = "bdmotion";

                    // Criar conexão
                    $conn = new mysqli($servername, $username, $password, $dbname);

                    // Checar conexão
                    if ($conn->connect_error) {
                        die("Conexão falhou: " . $conn->connect_error);
                    }

                    // Obter a lista de unidades para o select
                    $sql_unidades = "SELECT idUnidade, nomeUnidade FROM tbUnidade";
                    $result_unidades = $conn->query($sql_unidades);

                    // Filtrar por unidade se o filtro foi enviado
                    $filtroUnidade = isset($_GET['unidade']) ? $_GET['unidade'] : '';

                    // Definindo o número de registros por página
                    $registros_por_pagina = 5;

                    // Descobrir o número da página atual
                    $pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

                    // Calcular o offset
                    $offset = ($pagina_atual - 1) * $registros_por_pagina;

                    // Consulta SQL com filtro por unidade, se selecionado
                    $sql = "SELECT c.idCandidato, c.nomeCandidato, c.emailCandidato, c.telefoneCandidato, 
                            c.triagemCandidato, u.nomeUnidade 
                            FROM tbCandidato c 
                            JOIN tbUnidade u ON c.idUnidade = u.idUnidade";
                    
                    if ($filtroUnidade != '') {
                        $sql .= " WHERE c.idUnidade = '$filtroUnidade'";
                    }

                    $sql .= " LIMIT $registros_por_pagina OFFSET $offset";

                    // Recuperar o total de registros para calcular o número total de páginas
                    $sql_total = "SELECT COUNT(*) as total FROM tbCandidato";
                    if ($filtroUnidade != '') {
                        $sql_total .= " WHERE idUnidade = '$filtroUnidade'";
                    }
                    $result_total = $conn->query($sql_total);
                    if ($result_total) {
                        $row_total = $result_total->fetch_assoc();
                        $total_registros = $row_total['total'];
                    } else {
                        $total_registros = 0;
                    }

                    // Calcular o número total de páginas
                    $total_paginas = ceil($total_registros / $registros_por_pagina);
                    ?>

                    <!-- Formulário de filtro -->
                    <form method="GET" class="form-inline mb-2">
                        <!-- Filtro de Unidade -->
                        <div class="form-group mr-3">
                            <select class="form-control" id="unidade" name="unidade">
                            <option value="">Todas as Unidades</option>
                                <?php
                                if ($result_unidades->num_rows > 0) {
                                    while ($row_unidade = $result_unidades->fetch_assoc()) {
                                        $selected = $row_unidade['idUnidade'] == $filtroUnidade ? 'selected' : '';
                                        echo "<option value='{$row_unidade['idUnidade']}' $selected>{$row_unidade['nomeUnidade']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Botão de Submissão -->
                        <button type="submit" class="btn-green btn btn ml-3">Filtrar</button>
                    </form>

                    <?php
                    // Recuperar os dados para a página atual
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        echo '<table class="table table-bordered">';
                        echo '<thead>';
                        echo '  <tr>';
                        // echo '    <th>ID</th>';
                        echo '    <th>Nome</th>';
                        echo '    <th>Email</th>';
                        echo '    <th>Telefone</th>';
                        echo '    <th>Triagem</th>';
                        echo '    <th>Unidade</th>';
                        echo '    <th> </th>';
                        echo '  </tr>';
                        echo '</thead>';
                        echo '<tbody>';

                        while ($row = $result->fetch_assoc()) {
                            // Verificação segura dos valores
                            // $idCandidato = isset($row['idCandidato']) ? htmlspecialchars($row['idCandidato']) : 'N/A';
                            $nomeCandidato = isset($row['nomeCandidato']) ? htmlspecialchars($row['nomeCandidato']) : 'N/A';
                            $emailCandidato = isset($row['emailCandidato']) ? htmlspecialchars($row['emailCandidato']) : 'N/A';
                            $telefoneCandidato = isset($row['telefoneCandidato']) ? htmlspecialchars($row['telefoneCandidato']) : 'N/A';
                            $triagemCandidato = isset($row['triagemCandidato']) ? htmlspecialchars($row['triagemCandidato']) : 'N/A';
                            $nomeUnidade = isset($row['nomeUnidade']) ? htmlspecialchars($row['nomeUnidade']) : 'N/A';

                            echo '  <tr>';
                            // echo "    <td>{$idCandidato}</td>";
                            echo "    <td>{$nomeCandidato}</td>";
                            echo "    <td>{$emailCandidato}</td>";
                            echo "    <td>{$telefoneCandidato}</td>";
                            echo "    <td>{$triagemCandidato}</td>";
                            echo "    <td>{$nomeUnidade}</td>";
                            echo "    <td><a href='detalhesCandidato.php?id={$row['idCandidato']}' class='btn-green btn btn'>Detalhes</a></td>";
                            echo '  </tr>';
                        }

                        echo '</tbody>';
                        echo '</table>';

                        // Exibição da paginação
                       
                    } else {
                        echo '<div class="alert alert-warning center-message" role="alert">Nenhum candidato encontrado.</div>';
                    }
                    
                    $conn->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="pagination-container">
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php if ($pagina_atual > 1): ?>
                <li class="page-item"><a class="page-link btn-white btn btn-sm" href="?pagina=<?php echo $pagina_atual - 1; ?>&unidade=<?php echo $filtroUnidade; ?>">Anterior</a></li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?php if ($i == $pagina_atual) echo 'active'; ?>"><a class="page-link btn-white btn btn-sm" href="?pagina=<?php echo $i; ?>&unidade=<?php echo $filtroUnidade; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>

            <?php if ($pagina_atual < $total_paginas): ?>
                <li class="page-item"><a class="page-link btn-white btn btn-sm" href="?pagina=<?php echo $pagina_atual + 1; ?>&unidade=<?php echo $filtroUnidade; ?>">Próximo</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

    <!-- Modal de Acesso Negado -->
    <div class="modal fade" id="acessoNegadoModal" tabindex="-1" role="dialog" aria-labelledby="acessoNegadoModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header " style="color: red;">
                    <h5 class="modal-title" id="acessoNegadoModalLabel">Acesso Negado</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Você não tem permissão para acessar esta página.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-red btn" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            if (<?php echo isset($_SESSION['access_denied']) && $_SESSION['access_denied'] ? 'true' : 'false'; ?>) {
                $('#acessoNegadoModal').modal('show');
                
                // Espera 1 segundo (1000 ms) e depois redireciona
                setTimeout(function() {
                    $('#acessoNegadoModal').modal('hide');
                    window.location.href = '../Home/';
                }, 1000);
            }
        });
    </script>


</body>

</html>
