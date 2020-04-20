<?php
# @Author: Waris Agung Widodo <user>
# @Date:   2018-01-23T11:26:05+07:00
# @Email:  ido.alit@gmail.com
# @Filename: footer.php
# @Last modified by:   user
# @Last modified time: 2018-01-23T11:26:47+07:00
?>


<footer class="py-4 bg-grey-darkest text-grey-lighter">
    <div class="container">
        <div class="row py-4">
            <div class="col-md-3">
                <!-- <svg
                        class="fill-current text-grey-lighter block h-12 w-12 mb-2"
                        version="1.1"
                        xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                        viewBox="0 0 118.4 135" style="enable-background:new 0 0 118.4 135;"
                        xml:space="preserve">
                    <path d="M118.3,98.3l0-62.3l0-0.2c-0.1-1.6-1-3-2.3-3.9c-0.1,0-0.1-0.1-0.2-0.1L61.9,0.8c-1.7-1-3.9-1-5.4-0.1l-54,31.1
                    l-0.4,0.2C0.9,33,0.1,34.4,0,36c0,0.1,0,0.2,0,0.3l0,62.4l0,0.3c0.1,1.6,1,3,2.3,3.9c0.1,0.1,0.2,0.1,0.2,0.2l53.9,31.1l0.3,0.2
                    c0.8,0.4,1.6,0.6,2.4,0.6c0.8,0,1.5-0.2,2.2-0.5l53.9-31.1c0.3-0.1,0.6-0.3,0.9-0.5c1.2-0.9,2-2.3,2.1-3.7c0-0.1,0-0.3,0-0.4
                    C118.4,98.6,118.3,98.5,118.3,98.3z M114.4,98.8c0,0.3-0.2,0.7-0.5,0.9c-0.1,0.1-0.2,0.1-0.2,0.1l-20.6,11.9L59.2,92.1l-33.9,19.6
                    L4.6,99.7l0,0l0,0C4.2,99.5,4,99.2,4,98.8l0-62.5l0,0l0-0.1c0-0.4,0.2-0.7,0.5-0.9l20.8-12l33.9,19.6l33.9-19.6l20.6,11.9l0.1,0
                    c0.3,0.2,0.5,0.5,0.6,0.9l0,62.3L114.4,98.8L114.4,98.8z M95.3,68.6v39.4L23.1,66.4V26.9L95.3,68.6z"/>
                </svg> --> 
                <div class="mb-4"><?php echo $sysconf['library_name']; ?></div>
                <ul class="list-reset">
                    <li><a class="text-light" href="index.php?p=libinfo">Information</a></li>
                    <li><a class="text-light" href="index.php?p=services">Services</a></li>
                    <li><a class="text-light" href="index.php?p=librarian">Librarian</a></li>
                    <li><a class="text-light" href="index.php?p=member">Member Area</a></li>
                </ul>
            </div>
            <div class="col-md-5">
                <h4 class="mb-4">About Us</h4>
                <p>Gedung Arsip :
Jalan Arya Kemuning No. 02 Kel/Kec. Kuningan
Kode Pos : 45511
Telp/Fax : (0232) 8890174
e-Mail : info@disarsipus.kuningankab.go.id
              <br>
Gedung Perpustakaan :
Jalan Siliwangi Depan SMPN 1 Kuningan Kel/Kec. Kuningan
Kode Pos : 45511
Telp/Fax : (0232) 876114
e-Mail : info@disarsipus.kuningankab.go.id
perpusdakuningan@gmail.com</p>
            </div>
            <div class="col-md-4">
                <h4 class="mb-4">Pencarian</h4>
                <div class="mb-2">mulai dengan mengetik satu atau lebih kata kunci untuk judul, penulis atau subjek</div>
                <form action="index.php">
                    <div class="input-group mb-3">
                        <input name="keywords" type="text" class="form-control" placeholder="Enter keywords"
                               aria-label="Cari disini.."
                               aria-describedby="button-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit" value="search" name="search"
                                    id="button-addon2">Cari
                            </button>
                        </div>
                    </div>
                </form>
                <hr>
            </div>
        </div>
        <hr>
        <div class="flex font-thin text-sm">
            <p class="flex-1">&copy; <?php echo date('Y'); ?> &mdash; Senayan Developer Community</p>
            <div class="flex-1 text-right text-grey"> Supported by <code>SOLID Kuningan</code></small> | Powered by <code>SLiMS</code></div>
        </div>
    </div>
</footer>

<?php if ($sysconf['chat_system']['enabled'] && $sysconf['chat_system']['opac']) : ?>
    <div id="show-pchat2" style="position: fixed; bottom: 16px; right: 16px" class="shadow rounded">
        <button title="Chat" class="btn btn-primary"><i class="fas fa-comments mr-2"></i><?= __('Chat'); ?></button>
    </div>
<?php endif; ?>

<?php
// Chat Engine
include LIB . "contents/chat.php"; ?>

<!-- // Load modal -->
<?php include "_modal_topic.php"; ?>
<?php include "_modal_advanced.php"; ?>

<!-- // load our vue app.js -->
<script src="<?php echo assets('js/app.js?v=' . date('Ymd-his')); ?>"></script>
<script src="<?php echo assets('js/app_jquery.js?v=' . date('Ymd-his')); ?>"></script>
<?php include __DIR__ . "./../assets/js/vegas.js.php"; ?>
<?php if ($sysconf['chat_system']['enabled'] && $sysconf['chat_system']['opac']) : ?>
    <script>
        $('#show-pchat').click(() => {
            $('.s-chat').hide()
            $('#show-pchat2').show()
        })
        $('#show-pchat2').click(() => {
            $('.s-chat').show(300, () => {
                $('#show-pchat2').hide()
            })
        })
    </script>
<?php endif; ?>
</body>
</html>
