from io import BytesIO
from PIL import Image, ImageFilter, ExifTags
import boto3
import urllib
import piexif
import os
import json
import re
import traceback

# s3Res = boto3.resource('s3')

# s3Res = boto3.resource(
#   's3',
#   region_name=[your region, e.g. eu-central-1],
#   aws_access_key_id=[your access key],
#   aws_secret_access_key=[your secret key]
# )




s3Client = boto3.client('s3')
source_bucket = 'fridayadtest'  #os.environ['BUCKET_NAME'] 
key = 'AKIARNLPGFEMUWFRKZSN'   #os.environ['BUCKET_KEY']
DEF_IMG_PATH = 'http://textiletrends.in/gallery/1547020644No_Image_Available.jpg'  #os.environ['DEF_IMAGE_LOCATION']
BUCKET_URL = 'http://fridayadtest.s3-website.eu-west-2.amazonaws.com/'  #os.environ['URL']

widthExtract = 800;
heightExtract = 600;
angle = 0;
x = 0;
y = 0;
scale = 1;

def lambda_handler(event, context):
    """This main function is configured in the lambda function as to be executed first.
    """
    print(event)

    headerData = {'location': DEF_IMG_PATH}
    responseBody = {
        "error": 1,
        "path": DEF_IMG_PATH,
    }
    
    # TODO urllib.parse.unquote_plus(event['queryStringParameters']['key'])
    reqData = getDataFromParams(event['queryStringParameters'])

    if reqData is None:
        return {
            "statusCode": '200',
            "body": json.dumps(responseBody)
        }

    altered_img_url = BUCKET_URL + reqData['upload_path_thumb1']
    
    try:
        
        print('Downloading image from S3')
        input_bucket_data = s3Client.get_object(Bucket=source_bucket, Key=reqData['originalKey'])
        input_image_data = input_bucket_data['Body'].read()
        print('Downloaded finished')

        flagCompleted = handleImageOperations(input_image_data, reqData)

        if flagCompleted:
            if reqData.get("operation", None) == "New":
                headerData = {'location': altered_img_url}
            elif reqData.get("operation", None) == "Edit":
                responseBody = {
                    "error": 0,
                    "path": altered_img_url,
                }

    except Exception as e:
        print("Exception occurred in lambda handler")
        print(e)

    print("Returning json")

    if reqData.get("operation", None) == "New":
        return {
            "statusCode": '301',
            "headers": headerData,
            "body": ''
        }
    # elif reqData.get("operation", None) == "Edit":
    else:
        headerData = ""
        return {
            "statusCode": 200,
            "body": json.dumps(responseBody)
        }

def getDataFromParams(reqParams):
    """Process data from the API request
    """
    print("reqParams : before")
    print(reqParams)

    if reqParams is not None:
        print("Obtained reqParams")
        # intersected_list = reduce (set.intersection, map (set, [l1, l2, l3, l4]))
        reqKeys = reqParams.keys()

        reqParams['operation'] = None

        # If someone hits the API without the 'key' data in get params. Both "New"/"Edit" must contain "key".
        if 'key' not in reqParams:
            return None

        reqKeysSet = reqParams.keys()
        reqKeysDefs = {'key','x','y','scale','angle','image_type'}
        reqKeysIntersection = reqKeysSet & reqKeysDefs
        print(reqKeysIntersection)

        # If API request contains params required for Editing image then operation is "Edit".
        # else API request from S3 will have only 'key' in get params, hence "New".  
        if reqKeysIntersection == reqKeysDefs:
            reqParams['operation'] = "Edit"    
            reqParams['originalKey'] = reqParams['key'];
        else:
            reqParams = {'operation': "New", 'key': reqParams['key']}
            dimensionRegex = r'_((\d+)[xX](\d+))(.*)'
            matches = re.findall(dimensionRegex, reqParams.get('key'))
            print(matches)

            if not matches:
                print("No dimensions obtained from request.")
                return None

            match = matches[0]
            reqParams['width'] = int(match[1]);
            reqParams['height'] = int(match[2]);
            reqParams['image_type'] = match[5];
            
            if (reqParams['image_type']== 'advert') :
            	widthExtract = 800;
				heightExtract = 600;
	            if ((not (reqParams['width'] == 800 or reqParams['width'] == 300)) or
	                (not (reqParams['height'] == 600 or reqParams['height'] == 225))):
	                print("Request dimensions "+ str(reqParams['width']) +"X" + str(reqParams['height']) + " not allowed.")
	                return None
	            
	            reqParams['thumbnailKey'] = re.sub(r'(.jpg\?)(.*)', ".jpg", reqParams['key']);
	            reqParams['originalKey'] = re.sub("_" + match[0], "", reqParams['thumbnailKey']);
	
		        reqParams['originalKey'] = re.sub(r'(.jpg\?)(.*)', ".jpg", reqParams['originalKey']);
		        reqParams['upload_path_thumb1'] = re.sub(".jpg", "_800X600.jpg", reqParams['originalKey']);
		        reqParams['upload_path_thumb2'] = re.sub(".jpg", "_300X225.jpg", reqParams['originalKey']);
		    elif (reqParams['image_type']== 'user' || reqParams['image_type']== 'company') :
		    	widthExtract = 200;
				heightExtract = 150;
				
				reqParams['thumbnailKey'] = re.sub(r'(.jpg\?)(.*)', ".jpg", reqParams['key']);
	            reqParams['originalKey'] = re.sub("_" + match[0], "", reqParams['thumbnailKey']);
	
		        reqParams['originalKey'] = re.sub(r'(.jpg\?)(.*)', ".jpg", reqParams['originalKey']);
		        reqParams['upload_path_thumb1'] = re.sub(".jpg", "_org.jpg", reqParams['originalKey']);
		        reqParams['upload_path_thumb2'] = re.sub(".jpg", "_original.jpg", reqParams['originalKey']);
		        
		    else :
	            reqParams['originalKey'] = reqParams['key']);
	

    print("reqParams : after")
    print(reqParams)
    return reqParams
    
def handleImageOperations(image_data, reqData):
    """Handles all image operations based on request parameters.
    image_data : str
    download_path : str
    reqData : dict
    bool
    """
    download_path = reqData['originalKey']
    im = Image.open(BytesIO(image_data))

    global widthExtract
    global heightExtract
    angle = 0
    x = 0
    y = 0
    scale = 1
    reqWidth = widthExtract
    reqHeight = heightExtract
    global image_type = reqData.get('image_type')

    if reqData.get('operation') == None:
        return False

    if reqData.get('operation') == "Edit":
        angle = int(reqData.get('angle'))
        x = int(reqData.get('x'))
        y = int(reqData.get('y'))
        scale = float(reqData.get('scale'))
    else:
        reqWidth = int(reqData.get('width'))
        reqHeight = int(reqData.get('height'))

    # TODO TEMP DATA FOR TESTING. REMOVE WHILE TESTING WITH API OR S3 UPLOAD
    # angle = 180;
    # x = 17;
    # y = 61;
    # scale = 0.2315;

    imSize = list(im.size)
    print("imSize : ", imSize)

    exif_dict = None
    exif_bytes = b""
    operation = reqData['operation']

    if "exif" in im.info:
        exif_dict = piexif.load(im.info["exif"])

        # TODO START FLATTEN NOT WORKING TO GET WHITE BACKGROUND
        # im_whitebg = Image.new("RGBA", im.size, (255,255,255,255))
        # im.putalpha(255) # NOT WORKING
        # im_whitebg.paste(im, (0,0), im) # .convert('RGBA') # NOT WORKING
        # END FLATTEN NOT WORKING TO GET WHITE BACKGROUND

        # TODO NEED TO GET NEW WIDTH AND HEIGHT IF SWAPPED, FOR FURTHER USAGE
        im = fixImageOrientation(im, exif_dict, download_path, imSize)
        try:
            exif_bytes = piexif.dump(exif_dict)
        except Exception as e:
            # Delete Invalid EXIF Thumbnails
            del exif_dict["1st"]
            del exif_dict["thumbnail"]
            exif_bytes = piexif.dump(exif_dict)

    print("imSize after orientation fix : ", imSize)

    imWidth = imSize[0]
    imHeight = imSize[1]

    if(imSize[0] < imSize[1]):
        # For vertical images, swap extraction width and height only. 
        # While resizing IMAGE.thumbnail takes care of setting H&W based on vertical/horizintal image.
        if(image_type == 'advert'):
        	widthExtract = 600
        	heightExtract = 800
        elif(image_type == 'user' || image_type == 'company')
        	widthExtract = 150
        	heightExtract = 200
    try:

        if operation == "Edit":
            print("Altering image and creating thumbanail from request params.")

            widthResize = int(scale * float(imWidth))
            heightResize = int(scale * float(imHeight))
            print("widthResize : " + str(widthResize))
            print("heightResize : " + str(heightResize))
            
            im_resized = createThumbnail(im, widthResize, heightResize)

            print("Rotating image")
            im_resized_rotated = im_resized.rotate(-angle, expand=True)

            print("Cropping image")
            
            if(image_type == 'advert' image_type == 'user' || image_type == 'company'):
	            cropSize = (x, y, x+widthExtract, y+heightExtract)
	            im_thumb1 = im_resized_rotated.crop(cropSize)
        
	            print("Done image operations")
	
	            img_thumb1_byte_array = getImageByteArray(im_thumb1, exif_bytes)
	            flagUploadedThumb1 = uploadAlteredImageData(file_data = img_thumb1_byte_array, object_name=reqData['upload_path_thumb1'], bucket=source_bucket)
			
			if(image_type == 'advert'): 
	            im_thumb2 = createThumbnail(im_thumb1, widthResize=300, heightResize=225)
	
	            img_thumb2_byte_array = getImageByteArray(im_thumb2, exif_bytes)
	            flagUploadedThumb2 = uploadAlteredImageData(file_data = img_thumb2_byte_array, object_name=reqData['upload_path_thumb2'], bucket=source_bucket)

        elif operation == "New":
            print("Creating thumbanail from request params")
            im_thumb = createThumbnail(im, widthResize=reqWidth, heightResize=reqHeight)

            img_thumb_byte_array = getImageByteArray(im_thumb, exif_bytes)
            flagUploadedThumb = uploadAlteredImageData(file_data = img_thumb_byte_array, object_name=reqData['thumbnailKey'], bucket=source_bucket)

        else:
            print("No operation is performed")

    except Exception as e:
        print("Exception occurred in handleImageOperations")
        print(e)
        traceback.print_exc()
        return False

    return True

def fixImageOrientation(im, exif_dict, orig_image_path, imSize):
    """
    :im Input image data
    :exif_dict image exif data dictionary
    """
    # START OF CODE TO FIX ROTATION BASED ON ORIENTATION METADATA
    flagReplaceOrigImage = False
    img_orientation = 1

    try:

        if piexif.ImageIFD.Orientation in exif_dict["0th"]:
            img_orientation = exif_dict["0th"].pop(piexif.ImageIFD.Orientation)
            print("Orientation is ", img_orientation)

            if img_orientation != 1 and img_orientation != None:
                flagReplaceOrigImage = True

            if img_orientation == 2:
                print("Flipping image right")
                im = im.transpose(Image.FLIP_LEFT_RIGHT)
            elif img_orientation == 3:
                print("Rotating image 180 deg")
                im = im.rotate(180)
            elif img_orientation == 4:
                print("Rotating image 180 deg and flipping right")
                im = im.rotate(180).transpose(Image.FLIP_LEFT_RIGHT)
            elif img_orientation == 5:
                print("Rotating image -90 deg and flipping right")
                im = im.rotate(-90, expand=True).transpose(Image.FLIP_LEFT_RIGHT)
            elif img_orientation == 6:
                print("Rotating image -90 deg")
                im = im.rotate(-90, expand=True)
            elif img_orientation == 7:
                print("Rotating image 90 deg and flipping right")
                im = im.rotate(90, expand=True).transpose(Image.FLIP_LEFT_RIGHT)
            elif img_orientation == 8:
                print("Rotating image 90 deg")
                im = im.rotate(90, expand=True)
            
            # TODO NEED TO SWAP HEIGHT AND WIDTH AFTER IMAGE ORIENTATION FIX
            # TODO ALSO SET IN EXIF DATA IF ITS SWAPPED
            rotated_orientations = set([5,6,7,8])
            if img_orientation in rotated_orientations:
                print("Swapping height and width")
                imSize[0], imSize[1] = imSize[1], imSize[0]
                 
                if (piexif.ExifIFD.PixelXDimension in exif_dict["Exif"]) and (piexif.ExifIFD.PixelXDimension in exif_dict["Exif"]):
                    print("Swapping PixelDimension data in Exif")
                    exif_dict["Exif"][piexif.ExifIFD.PixelXDimension], exif_dict["Exif"][piexif.ExifIFD.PixelYDimension] \
                     = exif_dict["Exif"][piexif.ExifIFD.PixelYDimension], exif_dict["Exif"][piexif.ExifIFD.PixelXDimension]

        if "thumbnail" in exif_dict:
            print("Removing thumbnail data from exif")
            print(exif_dict["thumbnail"])
            exif_dict.pop("thumbnail")
            flagReplaceOrigImage = True

        # FINAL EXIF DATA
        try:
            exif_bytes = piexif.dump(exif_dict)
        except Exception as e:
            # Delete Invalid EXIF Thumbnails
            del exif_dict["1st"]
            del exif_dict["thumbnail"]
            exif_bytes = piexif.dump(exif_dict)
            
        # Replace orginal image if Exif is obtained and Orientation is fixed

        if flagReplaceOrigImage == True:
            print("Replacing original image after fixing Orientation")
            imgByteArrOrig = BytesIO()
            # TODO quality="keep" works only with input jpeg image. Need to set quality to ? if other image format.
            # Refer https://github.com/python-pillow/Pillow/issues/2238 as to why keep is to be used for quality.

            # The image quality, on a scale from 1 (worst) to 95 (best). The default is 75. Values above 95 should be avoided;
            # 100 disables portions of the JPEG compression algorithm, and results in large files with hardly any gain in image quality.
            # Refer https://pillow.readthedocs.io/en/latest/handbook/image-file-formats.html#jpeg
            im.save(imgByteArrOrig, format='JPEG', exif=exif_bytes, quality=95, optimize=True, progressive=True)
            imgByteArrOrig = imgByteArrOrig.getvalue()
            flagUploaded = uploadAlteredImageData(file_data = imgByteArrOrig, object_name=orig_image_path, bucket=source_bucket)

    except Exception as e:
        print("Exception occurred in fixImageOrientation")
        print(e)

    return im
    # END OF CODE TO FIX ROTATION BASED ON ORIENTATION METADATA

def getImageByteArray(im, exif_bytes):
    """Converts the image to Bytes string
    im : Image
    :exif_bytes
    """
    imgByteArr = BytesIO()

    # To convert images with alpha values to non-alpha valued image. Since JPG should not contain aplha value.
    if im.mode in ('RGBA', 'LA'):
        im = im.convert('RGB')

    # default value for quality=75.
    im.save(imgByteArr, format='JPEG', exif=exif_bytes, quality=92, optimize=True, progressive=True)
    return imgByteArr.getvalue()

def createThumbnail(im, widthResize, heightResize):
    """Creates the thumbnail of specified size from an image
    """
    print("Resizing image to " + str(widthResize) + "X" + str(heightResize))
    thumb = im.copy()
    thumb.thumbnail((widthResize, heightResize), Image.ANTIALIAS)
    print("resizing done")
    return thumb
    # im.resize() doesn't retain the image aspect ratio. Use im.thumbnail() instead
    # return im.resize((widthResize, heightResize))

def uploadAlteredImageData(file_data, bucket, object_name):
    """Uploads the image data to specified s3 bucket with provided image name
    """
    print('Uploading file : ' + str(object_name))

    try:
        s3Client.put_object(Bucket=bucket, Key=object_name, Body=file_data)
    except Exception as e:
        print(e)
        print('Uploading failed.')
        return False
        
    print('Uploading file done.')
    return True
        

def getHumanReadableOrientation(im):
    print("Getting Image Exif data")
    exif = {

        ExifTags.TAGS[k]: v
        for k, v in im._getexif().items()
        if k in ExifTags.TAGS

    }
    print("Exif data obtained")
    print(exif)
    img_orientation = exif.get('Orientation', 1)
    print(img_orientation)



