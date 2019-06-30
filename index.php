<html>
    <head>
        <Title>Image Uploading and Analyzing</Title>
        <style type="text/css">
            body { background-color: #fff; border-top: solid 10px #000;
                color: #333; font-size: .85em; margin: 20; padding: 20;
                font-family: "Segoe UI", Verdana, Helvetica, Sans-Serif;
            }
            h1, h2, h3,{ color: #000; margin-bottom: 0; padding-bottom: 0; }
            h1 { font-size: 2em; }
            h2 { font-size: 1.75em; }
            h3 { font-size: 1.2em; }
            table { margin-top: 0.75em; }
            th { font-size: 1.2em; text-align: left; border: none; padding-left: 0; }
            td { padding: 0.25em 2em 0.25em 0em; border: 0 none; }
        </style>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    </head>

    <body>
        <h1>Image Analyzing with Computer Vision</h1>

        <form role="form" method="POST" action="index.php?Upload" enctype="multipart/form-data">
            <label>Upload Image File</label>
            <input type="file" id="imageFile" name="imageFile">
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>

        <div id="wrapper" style="width:1020px; display:table;">
            <div id="jsonOutput" style="width:600px; display:table-cell;">
                Response:<br><br>
                <textarea id="responseTextArea" class="UIInput" style="width:550px; height:300px;" readonly="readonly"></textarea>
            </div>

            <div id="imageDiv" style="width:420px; display:table-cell;">
                Source image:<br><br>
                <img id="sourceImage" width="400" />
                <p><strong id="description"></strong></p>
            </div>
        </div>

        <?php
        require_once 'vendor/autoload.php';

        use MicrosoftAzure\Storage\Blob\BlobRestProxy;
        use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
        use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
        use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
        use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

        //$connectionString = "DefaultEndpointsProtocol=https;AccountName=".getenv('ACCOUNT_NAME').";AccountKey=".getenv('ACCOUNT_KEY');

        // Mengatur instance dari Azure::Storage::Client
        $connectionString = "DefaultEndpointsProtocol=https;AccountName=lastsubmission;AccountKey=/79HNasY5uVSvPiwXwjHMeSmh/a2CE7fYX0SjjyqcVhVI96X3IWYqmG7y7I6I3WjrqNhYmTgr1Ptqv0lhMH+qw==";

        // Membuat blob client
        $blobClient = BlobRestProxy::createBlobService($connectionString);

        // Membuat BlobService yang merepresentasikan Blob service untuk storage account
        $createContainerOptions = new CreateContainerOptions();

        $createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

        // Set container metadata
        $createContainerOptions->addMetaData("key1", "value1");
        $createContainerOptions->addMetaData("key2", "value2");

        // $containerName = "blockblobs".generateRandomString();
        $containerName = "blockblobsaoidyq";

        try {
            // Create container.
            // $blobClient->createContainer($containerName, $createContainerOptions);

            // List blobs.
            $listBlobsOptions = new ListBlobsOptions();
            //$listBlobsOptions->setPrefix("HelloWorld");

            echo "<table>";
            echo "<tr><th>File Name</th>";
            echo "<th>URL</th>";
            echo "<th>Action</th></tr>";

            do{
                $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
                foreach ($result->getBlobs() as $blob)
                {
                    echo "<tr><td>". $blob->getName()."</td>";
                    echo "<td>".$blob->getUrl()."</td>";
                    echo "<td><button onclick='processImage(this)'>Analyze image</button></td></tr>";
                }
            
                $listBlobsOptions->setContinuationToken($result->getContinuationToken());
            } while($result->getContinuationToken());
            echo "</table>";

            // Get blob.
            // echo "This is the content of the blob uploaded: ";
            // $blob = $blobClient->getBlob($containerName, $fileToUpload);
            // fpassthru($blob->getContentStream());
            // echo "<br />";
        }
        catch(ServiceException $e){
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/library/azure/dd179439.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }
        catch(InvalidArgumentTypeException $e){
            // Handle exception based on error codes and messages.
            // Error codes and messages are here:
            // http://msdn.microsoft.com/library/azure/dd179439.aspx
            $code = $e->getCode();
            $error_message = $e->getMessage();
            echo $code.": ".$error_message."<br />";
        }

        if (isset($_GET["Upload"])) {
            if (isset($_FILES["imageFile"])) {
                $fileToUpload = $_FILES['imageFile']['name'];
                $content = fopen($_FILES['imageFile']['tmp_name'], "r");
                //Upload blob
                $blobClient->createBlockBlob($containerName, $fileToUpload, $content);
                header("Refresh:0; url=index.php");
            }
        }
        ?>

    </body>


    <script type="text/javascript">
        $("#wrapper").hide();

        function processImage(evt) {
            var sourceImageUrl = $(evt).closest('td').prev('td').text();
            $("#wrapper").show();

            var subscriptionKey = "14f4966ed70548c29f1772e49a789e28";
            var uriBase = "https://southeastasia.api.cognitive.microsoft.com/vision/v2.0/analyze";
     
            // Request parameters.
            var params = {
                "visualFeatures": "Categories,Description,Color",
                "details": "",
                "language": "en",
            };
     
            // Display the image.
            document.querySelector("#sourceImage").src = sourceImageUrl;
     
            // Make the REST API call.
            $.ajax({
                url: uriBase + "?" + $.param(params),
                // Request headers.
                beforeSend: function(xhrObj){
                    xhrObj.setRequestHeader("Content-Type","application/json");
                    xhrObj.setRequestHeader(
                        "Ocp-Apim-Subscription-Key", subscriptionKey);
                },
                type: "POST",
                // Request body.
                data: '{"url": ' + '"' + sourceImageUrl + '"}',
            }).done(function(data) {
                // Show formatted JSON on webpage.
                $("#description").text(data.description.captions[0].text);
                $("#responseTextArea").val(JSON.stringify(data, null, 2));
            }).fail(function(jqXHR, textStatus, errorThrown) {
                // Display error message.
                var errorString = (errorThrown === "") ? "Error. " :
                    errorThrown + " (" + jqXHR.status + "): ";
                errorString += (jqXHR.responseText === "") ? "" :
                    jQuery.parseJSON(jqXHR.responseText).message;
                alert(errorString);
            });
        };
    </script>

</html>
