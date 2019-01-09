#!/bin/bash 

FileName=`basename $0`
work_path=$(dirname $0)
cd ./${work_path}  # 当前位置跳到脚本位置
work_path=$(pwd)   # 取到脚本目录
work_path='/data0/deploy/src'
log_date=`date +"%F %X"`
log_path=$work_path"/logs"
common_log_name='shell_error.log'
clone_log_name='shell_clone_error.log'
pull_log_name='shell_pull_error.log'
git=$(which git)
git="/usr/home/zhenpeng8/bin/bin/git"
alias git='/usr/home/zhenpeng8/bin/bin/git'
cb=`$git checkout -b `
ori="origin/"
pl=`$git pull `
co=`$git checkout `
gbr=`$git branch `
gitRepo='xxxx'

if [ ! -f $log_path"/"$common_log_name ]; then 
    touch $log_path "/" $common_log_name
fi
chmod - R 777 $log_path "/" $common_log_name

if [ $# == 5 ]; then
    sshAddr=$1
    proName=$2      # git仓库名
    actionName=$3   # git操作动作： clone pull
    branchName=$4    # 分支名称
    configId=$5
else
    echo "$LINENO" "$log_date" "Wrong params." >> $log_path "/" $common_log_name
    exit
fi

if [ ! -f $log_path"/"$configId"-"$clone_log_name ]; then
    touch $log_path "/" $configId "-" $clone_log_name
fi
chmod - R 777 $log_path "/" $configId "-" $clone_log_name

if [ ! -f $log_path"/"$configId"-"$pull_log_name ]; then
    touch $log_path "/" $configId "-" $pull_log_name
fi
chmod - R 777 $log_path "/" $configId "-" $pull_log_name


#echo $sshAddr $proName $actionName $branchName $configId

echo "项目路径: "$work_path"/"$proName
if [ ! -d $work_path"/"$proName ]; then
    chmod -R 777 $log_path"/"$configId"-"$clone_log_name
    echo "$LINENO"  "$log_date" "$work_path/$proName"  "项目不存在 start to clone" >> $log_path"/"$common_log_name
    if [ $actionName = "clone" ]; then
        #cd $work_path ; $git $actionName $gitRepo$proName".git" >> $log_path"/"$configId"-"$clone_log_name 2>&1     
        cd $work_path 
	$git $actionName $sshAddr >> $log_path"/"$configId"-"$clone_log_name 2>&1     
        if [ $branchName != 'master' ]; then
	    cd $work_path
            cd $proName 
            git checkout -b $branchName $ori$branchName
        fi

        if [ $? != 0 ]; then
            echo "$LINENO" $log_date" git clone failed." >> $log_path"/"$configId"-"$clone_log_name
            exit 1
        fi
    fi
fi
#else
#    if [ $actionName = "pull" ]; then
#        if [ $branchName == "master" ]; then
#            cd $work_path 
#	    cd $proName 
#	    git checkout $branchName
#	    $pl >> $log_path"/"$configId"-"$pull_log_name  2>&1
#        else
#            cd $work_path 
#	    cd $proName 
#            braArr=`git branch -a`
#            echo "$LINENO" "the pro branch arr: "$braArr
#            flag=0
#            for br in $braArr 
#            do
#                if [ $br == $branchName ]; then
#                    flag=1
#                    break
#                fi
#            done
#            echo "$LINENO" "The flag is "$flag >> $log_path"/"$configId"-"$pull_log_name
#            if [ $flag == 1 ]; then
#                echo "$LINENO" "Action: git pull cmd: "$co $branchName >> $log_path"/"$configId"-"$pull_log_name 2>&1
#                git checkout $branchName 
#                git pull
#            else 
#                echo "$LINENO" "Action: git pull cmd: "$cb $branchName $ori$branchName>> $log_path"/"$configId"-"$pull_log_name 2>&1
#                git checkout -b  $branchName $ori$branchName 
#                git pull
#            fi
#        fi
#
#        if [ $? != 0 ]; then
#            echo "$LINENO" $log_date" git pull failed." >> $log_path"/"$configId"-"$pull_log_name
#            exit 1
#        fi
#    else
#        echo "$LINENO" $log_date" the action name is wrong, check it ." >> $log_path"/"$common_log_name
#    fi
#fi
