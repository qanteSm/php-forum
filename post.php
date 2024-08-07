    <?php
    session_start();

    require_once "modules/mysqlconn.php";

    if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
        $userId = $_SESSION['id'];

        $sql = "SELECT username FROM accounts WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $username = htmlspecialchars($row['username']);
        } else {
            session_destroy();
            header("Location: giris.php?error=error");
            exit();
        }
        $stmt->close();
    }

    function kullaniciBegendiMi($conn, $postId, $userId) {
        $sql = "SELECT id FROM post_likes WHERE liker_id = ? AND liked_post = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        return ($result->num_rows > 0);
    }

    $statusbar = '<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Forum</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">';

    if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
        $statusbar .= '<li class="nav-item">
                            <a class="nav-link active" href="index.php">Ana Sayfa</a>
                        </li>                            
                        <li class="nav-item">
                            <a class="nav-link" href="modules/cikis.php">Çıkış Yap</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="userprofile.php?id='.$_SESSION['id'].'">' . $username . '</a>
                        </li>';
    } else {
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $statusbar .= '<li class="nav-item">
                            <a class="nav-link active"  href="index.php">Ana Sayfa</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active"  href="giris.php?yonlendirme_url='. urlencode($current_url) . '">Giriş Yap</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="kaydol.php">Kaydol</a>
                        </li>';
    }

    $statusbar .= '</ul>
            </div>
        </div>
    </nav>';

    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="utf-8">
    <title>Forum</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
        body {
            background: #eee;
            color: #708090;
        }
        .icon-1x {
            font-size: 24px !important;
        }
        a {
            text-decoration: none;
        }
        .text-primary, a.text-primary:focus, a.text-primary:hover {
            color: #00ADBB!important;
        }
        .text-black, .text-hover-black:hover {
            color: #000 !important;
        }
        .font-weight-bold {
            font-weight: 700 !important;
        }
        .comment-container {
        background-color: #f0f0f0;
        border-radius: 8px;
        padding: 5px;
        margin-bottom: 15px;
        font-weight: 500; 
    }
        .comment-content {
            padding: 5px 10px; 
            font-size: 14px; 
        }
        .post-title {
        color: black; 
    }

    .like-button {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
    }

    .heart-icon {
        font-size: 1.5em;
        color: #ccc; 
        transition: color 0.3s;
    }

    .heart-icon.filled {
        color: red; 
    }

    .highlighted-comment {
        background-color: #ffffcc; 
        border: 1px solid #ffff00; 
    }
    .user-mention {
        background-color: #e9f5ff; /* Mavi arkaplan */
        padding: 3px 5px;
        border-radius: 10px; /* Yuvarlatılmış kenarlar */
        font-weight: bold;
    }
    .emoji-panel {
            display: none;
            position: absolute;
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 5px;
            border-radius: 5px;
            max-height: 90px; /* Panel yüksekliğini 3 satır emoji için ayarlayın */
            overflow-y: auto; /* Dikey kaydırma çubuğunu etkinleştir */
            max-width: 200px;
        }

        .emoji-panel.active {
            display: block;
        }

        .emoji-panel span {
            font-size: 20px;
            cursor: pointer;
            margin: 2px;
            display: inline-block; /* Emoji'leri yan yana sığdır */
            width: 18%; /* Her emoji'ye %25 genişlik ver (4 emoji/satır) */
            text-align: center; /* Emoji'leri ortala */
        }
    a {
    color: inherit; 
    text-decoration: none; 
    }

    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    </head>
    <body>

    <?php echo $statusbar; ?> 

    <div class="container mt-5">
        <div class="row">

            <?php
            require_once "modules/ranks.php";
            if(isset($_SESSION['entered']) && $_SESSION['entered'] === true && $_SESSION['id']){

                $userRankLevel = getRankLevelById($conn, $_SESSION['id']);
            } else {
                $userRankLevel = 0;
            }
            $postId = isset($_GET['post']) ? (int)$_GET['post'] : null;
            $messageIndex = isset($_GET['message']) ? (int)$_GET['message'] - 1 : null; 

            if ($postId) {
                $sql = "SELECT p.*, a.username AS yazar, a.profil_fotografi AS yazar_profil_fotografi, a.id AS yazar_id
                FROM posts p
                INNER JOIN accounts a ON p.yazar = a.id 
                WHERE p.id = ?
                AND p.post_rank <= ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $postId,$userRankLevel);
                $stmt->execute();
                $postResult = $stmt->get_result();

                if ($postResult->num_rows > 0) {

                        if (isset($_SESSION['entered']) && $_SESSION['entered'] === true && $_SESSION['id'] && $postId) {
                            $visitTime = round(microtime(true) * 1000);
                            $sql = "INSERT INTO post_visits (visitor_id, visited_post_id, tarih) VALUES (?, ?, ?)";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("iii", $_SESSION['id'], $postId, $visitTime);
                            $stmt->execute();
                            $stmt->close();
                        }

                    if (isset($_GET['status']) && $_GET['status'] === 'success') {
                        echo '<div class="alert alert-success" role="alert">
                                Post başarıyla oluşturuldu!
                            </div>';
                    }


                    function kullaniciAdiniBul($metin, $conn) {
                        $desen = '/\(user@(\d+)\)/'; 
                        preg_match_all($desen, $metin, $eslesmeler);
                    
                        if (!empty($eslesmeler)) {
                            foreach ($eslesmeler[1] as $kullaniciId) {
                                $sql = "SELECT username FROM accounts WHERE id = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $kullaniciId);
                                $stmt->execute();
                                $result = $stmt->get_result();
                    
                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    $username = $row['username'];
                                    $metin = str_replace(
                                        "(user@{$kullaniciId})", 
                                        "<a href='userprofile.php?id={$kullaniciId}' class='user-mention'>@{$username}</a>", 
                                        $metin
                                    );
                                }
                                $stmt->close();
                            }
                        }
                        return $metin;
                    }

                    $row = $postResult->fetch_assoc();

                    $gecikenSaniye = round(time() - ($row["tarih"] / 1000));
                    $dakika = round($gecikenSaniye / 60);
                    $saat = round($dakika / 60);
                    $gun = round($saat / 24);
                    $ay = round($gun / 30);
                    $yil = round($ay / 12);

                    if ($gecikenSaniye < 60) {
                        $yayinlanmaZamani = "$gecikenSaniye saniye önce";
                    } elseif ($dakika < 60) {
                        $yayinlanmaZamani = "$dakika dakika önce";
                    } elseif ($saat < 24) {
                        $yayinlanmaZamani = "$saat saat önce";
                    } elseif ($gun < 30) {
                        $yayinlanmaZamani = "$gun gün önce";
                    } elseif ($ay < 12) {
                        $yayinlanmaZamani = "$ay ay önce";
                    } else {
                        $yayinlanmaZamani = "$yil yıl önce";
                    }

                    echo '<div class="col-lg-12">
                    <div class="card row-hover pos-relative py-3 px-3 mb-3 border-warning border-top-0 border-right-0 border-bottom-0 rounded-0">
                        <div class="row align-items-center">
                            <div class="col-md-1">
                                <img src="uploads/profil-fotograflari/' . $row['yazar_profil_fotografi'] . '" alt="Profil Fotoğrafı" class="rounded-circle" style="width: 75px; height: 75px;"> 
                            </div>
                            <div class="col-md-11">
                                <h3><span class="post-title">' . $row["baslik"] . '</span></h3> 
                                <div class="comment-container">  
                                        '.kullaniciAdiniBul(htmlspecialchars($row['icerik']), $conn).'
                                    
                                </div>
                                <p class="text-sm">
                                <span class="op-6">Posted by</span> <a class="text-black" href="userprofile.php?id='.$row["yazar_id"].'">' . $row["yazar"] . '</a> <span class="op-6">-</span> <a class="text-black">' . $yayinlanmaZamani . '</a> |';
                            $etiketler = explode(',', $row["etiketler"]);
                            foreach ($etiketler as $etiket) {
                                echo '<a class="text-black mr-2" href="tumpostlar.php?etiket='.trim($etiket).'">  <strong>#' . trim($etiket) . '</strong></a>'; 
                            }
                            
                            echo ' | '; 

                            if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
                                $begeniDurumu = kullaniciBegendiMi($conn, $postId, $_SESSION['id']) ? 'filled' : '';
                                echo '<button class="like-button" data-post-id="' . $postId . '">
                                    <i class="bi bi-heart-fill heart-icon ' . $begeniDurumu . '"></i>
                                </button>';
                            } else {
                                echo '<i class="bi bi-heart-fill heart-icon"></i>'; 
                            }
                            if (!empty($row['resim'])) {
                                                echo '<br><br>'; 
                                                echo '<a href="#" onclick="openImageModal(\'' . $row['resim'] . '\')">
                                                        <img src="' . $row['resim'] . '" alt="Post Resmi" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                                    </a>';
                                            } 
                            echo '</p> 
                                <br>
                                <div class="text-sm op-5">';
                            echo '</div>
                            </div>
                        </div>
                    </div>
                </div>';

                    // Yorumlar bölümü
                    $sql = "SELECT y.yorum_metni, y.yorum_tarihi, a.username, a.id as kullanici_id 
                            FROM replys y
                            INNER JOIN accounts a ON y.kullanici_id = a.id
                            WHERE y.post_id = ?
                            ORDER BY y.yorum_tarihi ASC"; 

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $postId);
                    $stmt->execute();
                    $yorumlarResult = $stmt->get_result();

                    if ($yorumlarResult->num_rows > 0) {
                        echo '<div class="col-lg-12">';
                        $yorumSayisi = 0;
                        while ($yorum = $yorumlarResult->fetch_assoc()) {
                            $yorumcuId = $yorum['kullanici_id']; 
                            $yorumcuProfilFotosu = "fotoyok.jpg"; 

                            $sql = "SELECT profil_fotografi FROM accounts WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $yorumcuId);
                            $stmt->execute();
                            $profilFotoResult = $stmt->get_result();

                            if ($profilFotoResult->num_rows > 0) {
                                $profilFotoRow = $profilFotoResult->fetch_assoc();
                                if (!empty($profilFotoRow['profil_fotografi'])) {
                                    $yorumcuProfilFotosu = $profilFotoRow['profil_fotografi'];
                                }
                            }
                            $stmt->close();

                            $highlightedClass = ($yorumSayisi === $messageIndex) ? 'highlighted-comment' : '';

                            echo '<div class="card row-hover pos-relative py-3 px-3 mb-3 border-light ' . $highlightedClass . '">
                                <div class="row align-items-center">
                                    <div class="col-md-1 d-flex align-items-center"> 
                                        <img src="uploads/profil-fotograflari/' . $yorumcuProfilFotosu . '" alt="Profil Fotoğrafı" class="rounded-circle" style="width: 60px; height: 60px;"> 
                                    </div>
                                    <div class="col-md-11">
                                        <div class="d-flex align-items-center"> 
                                            <p><strong><a href="userprofile.php?id=' . $yorumcuId . '">' . htmlspecialchars($yorum['username']) . '</a></strong> <small>(' . date('d.m.Y H:i', strtotime($yorum['yorum_tarihi'])) . ')</small></p>
                                        </div>
                                        <div class="comment-container">  
                                            <div class="comment-content">
                                                '.kullaniciAdiniBul(htmlspecialchars($yorum['yorum_metni']), $conn).'

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>';
                            $yorumSayisi++;
                        }
                        echo '</div>';
                    } else {
                        echo '<p>Bu posta henüz yorum yapılmamış.</p>';
                    }

                    if (isset($_SESSION['entered']) && $_SESSION['entered'] === true) {
                    
                            if(isset($_GET['error'])){
                                echo '<div class="alert alert-danger error-message" role="alert">' . htmlspecialchars($_GET['error']) . '</div>';   
                            }
                            
                        echo '<div class="col-lg-12">
                                <div class="card row-hover pos-relative py-3 px-3 mb-3 border-light">
                                    <form action="modules/replypost.php" method="post">
                                        <input type="hidden" name="post_id" value="' . $postId . '">
                                        <div class="form-group">
                                            <textarea class="form-control" name="yorum" rows="3" placeholder="Yorumunuzu buraya yazın..."></textarea>
                                        </div>
                                        <div class="emoji-container mt-2">
                                        <button type="button" id="toggleEmojiPanel" class="btn btn-light"><i class="bi bi-emoji-smile"></i></button>
                                        <div class="emoji-panel" id="emojiPanel">
                                            <span>😄</span>
                                            <span>😁</span>
                                            <span>😂</span>
                                        </div>
                                    </div>
                                        <button type="submit" class="btn btn-primary mt-2">Yorum Yap</button>
                                    </form>
                                </div>
                            </div>';
                    } else {
                        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        echo '<p>Yorum yapmak için <a href="giris.php?yonlendirme_url='. urlencode($current_url) . '">giriş yapın</a> veya <a href="kaydol.php">kaydolun</a>.</p>';
                    }

                } else {
                    echo'<div class="container py-5">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1>Geçerli Bir Post ID Girin! (Post silinmiş olabilir)</h1>
                    <p>Bir post görüntülemek için URL\'ye post IDsini ekleyin (örneğin: post.php?post=2).</p>
                </div>
            </div>
        </div>';
                }

            } else {
                echo'<div class="container py-5">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h1>Geçerli Bir Post ID Girin!</h1>
                    <p>Bir post görüntülemek için URL\'ye post IDsini ekleyin (örneğin: post.php?post=2).</p>
                </div>
            </div>
        </div>';
            }

            $conn->close();
            ?>

        </div>
    </div>
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl"> 
        <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
            <img src="" id="modalImage" class="img-fluid" alt="Post Resmi">
        </div>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>

    const highlightedComment = document.querySelector('.highlighted-comment');
    if (highlightedComment) {
            highlightedComment.scrollIntoView({
                behavior: 'smooth', 
                block: 'center' 
            });
        }
        const errorMessage = document.querySelector('.error-message');
    if (errorMessage) {
        errorMessage.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center'
        });
    }
        const likeButtons = document.querySelectorAll('.like-button');

        likeButtons.forEach(button => {
            button.addEventListener('click', () => {
                const postId = button.dataset.postId;
                const heartIcon = button.querySelector('.heart-icon');

                fetch(`modules/like_post.php`, { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `post_id=${postId}` 
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP hatası! Durum: ' + response.status);
                        }
                        return response.json(); 
                    })
                    .then(data => {
                        if (data.success) {
                            heartIcon.classList.toggle('filled');
                        } else {
                            console.error('Hata Detayları:', data); n 
                            let postData = '';
                            for (const key in data.post) {
                                postData += `${key}: ${data.post[key]}, `;
                            }
                            if (postData) {
                                postData = postData.slice(0, -2); 
                            } else {
                                postData = 'POST verisi yok';
                            }
                            alert('Beğeni işlemi başarısız! ' + data.message+' | '+ postData);
                        }
                    })
                    .catch(error => {
                        console.error('Hata:', error);
                        alert('Beğeni işlemi sırasında bir hata oluştu! Lütfen tekrar deneyin.');
                    });
            });
        });
        function openImageModal(imageUrl) {
        document.getElementById('modalImage').src = imageUrl;

        var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        imageModal.show();
    }
    const toggleEmojiPanelBtn = document.getElementById('toggleEmojiPanel');
        const emojiPanel = document.getElementById('emojiPanel');
        const yorumTextarea = document.querySelector('textarea[name="yorum"]'); // Yorum alanını seçin

        // Emoji listesini (daha fazla emoji ekleyebilirsiniz)
        const emojiList = [
            "😀", "😁", "😂", "🤣", "😃", "😄", "😅", "😆", "😉", "😊", "😋", "😎", "😍",
            "😘", "😗", "😙", "😚", "🙂", "🤗", "🤩", "🤔", "🤨", "😐", "😑", "😶", "🙄",
            "😏", "😣","😥", "😮", "🤐", "😯", "😪", "😫", "😴", "😌", "😛", "😜", "😝", "🤤",
            "😒", "😓", "😔", "😕", "🙃", "🤑", "😲", "🙁", "😖", "😞", "😟",
            "😤", "😢", "😭", "😦", "😧", "😨", "😩","🥺", "🤯", "😬", "😰", "😱", "🥵", "🥶", "😳", "🤪", "😵", "🤬",
            "😡", "😠", "😤", "😥", "😨", "😰", "😢", "😭", "😓", "😩", "😖", "😞",
            "😟", "😤", "😢", "😭", "😦", "😧", "😨", "😩", "🤯", "😬", "😰",
            "😱", "😳", "🤪", "😵", "😡", "😠", "🤬", "🤮", "🤧", "🤒", "🤕",
            "🤢", "🤮", "🤧", "😇", "🤠", "🥳", "🥺", "🤓", "🧐", "😕", "😟", 
            "😳", "😬", "😢", "🤯", "🥰", "🤩", "🤗", "🙂", "🙃", "😉", "😊", 
            "😌", "😍", "😘", "😗", "😙", "😚", "😛", "😝", "😜", "🤪", "🤨", 
            "🧐", "🤓", "😎", "🤩", "🥳", "😏", "😒", "😞", "😔", "😕", "😟",
            "😭", "😩", "🥺", "😳", "😱", "😨", "😰", "😥", "😓", "😨"
        ]; 
        emojiList.forEach(emoji => {
    let emojiContainer = document.createElement('div'); // Emoji için bir kapsayıcı div oluşturun
    emojiContainer.style.display = 'inline-block'; // Div'i satır içi blok yapın
    emojiContainer.style.width = '25%'; // Her emoji'ye %25 genişlik verin

    let emojiSpan = document.createElement('span');
    emojiSpan.innerText = emoji;
    emojiSpan.addEventListener('click', () => {
        yorumTextarea.value += emoji;
        yorumTextarea.dispatchEvent(new Event('input'));
        emojiPanel.classList.remove('active'); 
    });

    emojiContainer.appendChild(emojiSpan); // Span'i div'in içine ekleyin
    emojiPanel.appendChild(emojiContainer); // Div'i panele ekleyin
});
        toggleEmojiPanelBtn.addEventListener('click', () => {
            emojiPanel.classList.toggle('active');
        });
    </script>
    </body>
    </html>